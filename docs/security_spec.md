# Security Specifications

## Data Invariants
1. Users cannot change their own roles in their profile. Only admins can update another user's role. Super-admin can do anything.
2. `borrow_records` can only be created by an authenticated user. The `borrowerEmail` must match the authenticated user.
3. `inventory` can only be created or updated by admins. Users can only read.
4. Ratings can only be created with the user's own `userId`.

## The "Dirty Dozen" Payloads
1. **User Profile - Role Spoof (Update)**: A normal user payload containing `role: 'admin'`.
2. **User Profile - Cross-User (Update)**: A normal user trying to update a user ID not matching `request.auth.uid`.
3. **Borrow Record - Identity Spoof (Create)**: A user sending a create payload with `borrowerEmail` set to another user's email.
4. **Inventory Item - Unauth (Create)**: A normal user trying to create an inventory item.
5. **Inventory Item - Unauth (Update)**: A normal user modifying inventory item `quantity`.
6. **Notification - Cross-User (Create)**: A user sending a notification to another user pretending to be the system.
