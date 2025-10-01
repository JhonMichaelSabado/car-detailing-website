# TODO: Fix Reset Password Link Expiry/Invalid Issue

## Steps:
- [x] Edit authenticate.php: Set PHP timezone to 'Asia/Manila', extend expiry to 24 hours (time() + 86400), add logging for token update success/failure.
- [x] Edit reset_password.php: Add debug logging for token validation (log token, query results, current time vs expiry).
- [x] Set MySQL timezone to '+08:00' via SQL command.
- [x] Test forgot password flow: Request reset, verify token/expiry in DB, click link, reset password, confirm login with new password.
