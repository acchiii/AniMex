# TODO

## Admin overlay login (light/dark aware)
- [ ] Add admin login overlay modal partial under `resources/views/layouts/partials/`.
- [ ] Include admin modal in `resources/views/layouts/admin.blade.php` (or gate it when not admin).
- [ ] Add an admin login trigger/button on the admin layout for non-admin users.
- [ ] Adjust `AdminMiddleware` to show admin-login-capable view/behavior instead of immediate 403.
- [ ] Implement an admin login handler (route/controller) that authenticates and redirects to `/admin`.
- [ ] Manual test: open `/admin` as non-admin; modal should open and follow theme; submit login should grant access.

