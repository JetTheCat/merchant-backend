# Stripe Webhook Testing

Guide for simulating Stripe payment intent events locally during development.

## Prerequisites

- [Stripe CLI](https://stripe.com/docs/stripe-cli) installed and authenticated (`stripe login`)
- Local app running inside Docker (`docker-compose up`)
- Stripe listener forwarding to your local app (see below)

## Start the Listener

Run this before triggering any events. It forwards Stripe webhook events to your local app and automatically signs them with a test webhook secret.

```bash
stripe listen --forward-to localhost:80/api/webhooks/stripe
```

Keep this running in a separate terminal throughout your testing session.

---

## Simulating a Payment Intent Succeeded Event

The most reliable way to trigger a real `payment_intent.succeeded` event for an existing payment intent is to confirm it via the Stripe CLI using a test card. This causes Stripe to actually process the intent and fire the webhook with a real charge attached — which the app needs to resolve the payment method and call `grantCredits()`.

```bash
stripe payment_intents confirm <payment_intent_id> \
  --payment-method pm_card_visa
```

**Example:**
```bash
stripe payment_intents confirm pi_3ToKzj21zcKweSAd0U65Op1W \
  --payment-method pm_card_visa
```

Stripe will send a `payment_intent.succeeded` event to your listener with a valid `latest_charge` ID. The app will:
1. Look up the `Payment` record by `stripe_payment_intent_id`
2. Retrieve the charge from Stripe to resolve the payment method
3. Call `CreditPurchaseService::grantCredits()` to complete the credit purchase

### Common Test Payment Methods

| Method | ID | Behaviour |
|---|---|---|
| Visa (succeeds) | `pm_card_visa` | Confirms immediately |
| Mastercard (succeeds) | `pm_card_mastercard` | Confirms immediately |
| Card declined | `pm_card_visa_chargeDeclined` | Triggers `payment_intent.payment_failed` |
| Insufficient funds | `pm_card_chargeCustomerFail` | Triggers `payment_intent.payment_failed` |

---

## Resending an Existing Event

If a webhook event was already fired by Stripe but your local app missed it (e.g. listener wasn't running), you can resend it by event ID.

**Find the event ID:**
```bash
stripe events list --type payment_intent.succeeded
```

To narrow it down to a specific payment intent:
```bash
stripe events list --type payment_intent.succeeded | grep -B5 <payment_intent_id>
```

**Resend it:**
```bash
stripe events resend evt_XXXXXXXXXXXXXXXX
```

Alternatively, resend from the **Stripe Dashboard**: Developers → Workbench → Events → find the event → **Resend**.

---

## Simulating a Payment Failed Event

```bash
stripe payment_intents confirm <payment_intent_id> \
  --payment-method pm_card_visa_chargeDeclined
```

This triggers `payment_intent.payment_failed`, which calls `CreditPurchaseService::failPurchase()` and marks the transaction as failed.

---

## Why `stripe trigger` Doesn't Work Here

`stripe trigger payment_intent.succeeded` creates a fully synthetic event with a new payment intent ID. It cannot be overridden with an existing ID, and the synthetic charge it generates cannot be fetched via the Stripe API. Since the app calls `StripeService::retrieveCharge()` to get the real charge data, synthetic events will fail mid-processing.

Always use `stripe payment_intents confirm` or `stripe events resend` when testing against real payment intent records.
