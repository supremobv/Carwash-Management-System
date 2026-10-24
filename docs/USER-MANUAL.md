# User manual

## First-time configuration

Sign in with your administrator account. Open **Settings**. Set the business name, tagline, contact details, address, currency, timezone, tax rate and receipt message. Upload a square PNG/JPG/WebP logo (maximum 2 MB and 2000 × 2000 pixels). Saving applies branding throughout the workspace and receipts. “Restore sample logo” switches back to AquaFlow.

Choose currency and timezone **before** entering real orders or expenses. They lock afterward to protect historical amounts and dates. All supported currencies use two decimal places. Changing tax affects new orders only. Business branding and receipt contact information remain editable.

Open **Services** to edit the example menu. Add the services you offer, prices and estimated durations. Prices apply to all vehicle sizes in this MVP. Deactivate services you no longer sell; historical orders retain the original service names and prices. Open **Wash bays** and set up active bays. Deactivate bays that are out of use; an already active wash still needs to be finished.

Open **Team & access** to create individual accounts. Use unique emails and passwords of at least 12 characters. Give staff the smallest role they need. Each user can change their password through their name in the top right. An administrator can reset another user’s password by editing the team member; leave password fields blank to keep the current password. A reset invalidates the user’s existing sessions when they next make an authenticated request. Disable inactive staff instead of deleting their history.

## Role permissions

| Feature | Admin | Manager | Cashier | Wash team |
|---|---|---|---|---|
| Dashboard | Yes | Yes | Yes | Redirects to own orders |
| Customers & vehicles | Yes | Yes | Yes | No |
| Create orders / bookings | Yes | Yes | Yes | No |
| Assign bay / staff & start wash | Yes | Yes | Yes | No |
| View orders | All | All | All | Assigned only |
| Mark ready / handover | Yes | Yes | Yes | Assigned only |
| Collect payment / receipt | Yes | Yes | Yes | No |
| Record refund | Yes | Yes | No | No |
| Services & bays | Yes | Yes | No | No |
| Inventory & stock history | Yes | Yes | No | No |
| Expenses | Yes | Yes | Yes | No |
| Reports & CSV | Yes | Yes | No | No |
| Activity log | Yes | Yes | No | No |
| Staff accounts / roles | Yes | No | No | No |
| Business settings | Yes | No | No | No |
| Change own password | Yes | Yes | Yes | Yes |

Wash team members can see operational prices/balances on their assigned orders, but cannot access payment history, customer management or reports. This is a fixed, server-enforced permission matrix; custom role editing is outside the MVP. The active administrator cannot disable or demote their own account.

## Register a customer and vehicle

1. Open **Customers → Add customer**. Enter name and phone; email and notes are optional.
2. Open **Vehicles → Add vehicle**. Select the owner, enter the unique plate, make/model, size and optional color.
3. Save. License plates are trimmed and stored in uppercase. If a plate already exists, search Vehicles and edit the existing record.

Use notes for operational preferences only. Avoid recording card details or other unnecessary sensitive information. Records are retained; there is no general customer-delete button in this MVP.

## Walk-in order

1. Select **New wash order**.
2. Choose the registered vehicle. Add the customer and vehicle first if needed.
3. Select one or more services. Add an optional discount and instructions.
4. Leave booking time empty for a walk-in. Review the estimated amount and create the order.
5. The server calculates subtotal, discount, tax and total. The saved order opens in **Waiting**.

Discount cannot exceed subtotal. The estimate is for convenience; the server’s stored total is authoritative. There is no silent repricing after creation. To correct services on an unstarted order, refund any payments, cancel it and create a replacement.

## Bookings

Enter a future date/time when creating the order. It starts as **Scheduled** and appears under **Booked** in Wash orders. A booking records an arrival time; it does not reserve a bay or enforce slot capacity. When the customer arrives, open the order and select **Check in customer** to move it to Waiting. To change the appointment, cancel the unpaid booking and create a new one.

## Wash floor workflow

1. Open a Waiting order. Select an active bay and active wash team member (or manager/admin) and choose **Start washing**.
2. The server prevents assigning a bay or staff member who is already Washing another vehicle.
3. Complete the services and quality check, then choose **Mark ready for pickup**. The bay and staff member become available for a new wash.
4. Collect the remaining balance, then choose **Complete & hand over**.

Only valid forward transitions are accepted. Completed and cancelled orders keep their history. Wash team accounts can update only their own assigned orders, and cannot start new washes or cancel orders. An order cannot be completed while a balance remains. Zero-total orders can be completed without a payment.

## Payments, refunds and receipts

In an order, **Payment summary** shows total, net collected and balance due. Enter the amount, select Cash/Card/Bank/E-wallet, add an optional external reference and choose **Record payment**. Partial payments are supported. You cannot collect more than the outstanding balance. Duplicate submissions using the same payment form key do not add another payment.

The application records payments received outside the system. It does not charge a card or connect to a payment gateway. Do not enter card numbers or security codes.

Administrators and managers can expand **Record a refund**. Return the funds externally first, enter the amount and reason/reference, and confirm. A refund adds a negative ledger entry, reduces net collections and restores the balance. You cannot refund more than the net collected amount. Refunds on completed orders leave the completion history intact and appear as an outstanding balance; collect again if applicable, or handle the commercial credit according to your business procedure. This MVP does not implement credit notes or cancellation after washing starts.

To cancel a Scheduled or Waiting order, refund its payments first, then choose **Cancel order**. A cancelled order cannot receive payments. For **Receipt**, open the printable view and select **Print / Save PDF**. It shows services, tax, discounts, payment/refund entries and current balance. Confirm required invoice fields and tax treatment for your business before using it as an official fiscal document.

## Inventory

Add inventory items with a unit (L, pcs, kg) and low-stock threshold. New items start at zero. In the inventory table, enter a positive stock quantity and reason to record opening stock or a delivery. Use negative quantities for consumption, wastage or correction. The server prevents a negative final balance. **History** shows every movement and its staff member. The dashboard flags balances at or below the threshold.

Service completion does not automatically deduct supplies. Record actual usage manually. Keep each item in a single consistent unit.

## Expenses and reports

Record expenses with date, category, description and amount. Future expense dates are rejected. Editing an expense updates that record and writes an activity entry; the activity log is not a full before/after accounting journal.

Managers/admins can open **Reports**, select dates and download CSV. Reports use payment dates for collections/refunds and expense dates for operating expenses. “Net cash after expenses” includes tax in collections and is not accounting profit. Outstanding balance is all-time and includes future bookings; it is independent of the selected report period. Receipts/CSV values use the configured currency, and business dates follow the configured timezone.

## Common errors and recovery

- **Required details:** Read the message above the form, correct the named fields and submit. Most non-sensitive form values are retained. File selections and passwords must be entered again.
- **Bay or staff busy:** Another wash is using the selected resource. Choose a different bay/person or finish the active wash.
- **Balance remains:** Record the outstanding payment before completing the order.
- **Too many login attempts:** Wait for the displayed retry interval. Ask the administrator for a password reset if necessary.
- **Inactive account / access denied:** Contact the administrator; changing the browser URL cannot grant permission.
- **Session expired:** Reload the form and sign in again if needed. Check the order before retrying a payment. Payment keys prevent replaying the same submitted form, but use the ledger to verify whether a new attempt is needed.
- **Server error:** Avoid repeatedly submitting payments; inspect the order first and ask the administrator to review the application log.

Sign out from the sidebar when leaving a shared workstation. The default idle session lifetime is 120 minutes. On small screens, use the menu button in the header to open navigation.
