---
title: Known Issues
weight: 2
---

Fixed:

- [This cache store is not supported. - torann/geoip](https://github.com/Torann/laravel-geoip/issues/147#issuecomment-528414630)
- When the session renews Laravel fires the Login event which results in a new login row [Issue #13](https://github.com/rappasoft/laravel-authentication-log/issues/13) - **Fixed in v4.0.0**: The package now automatically detects session restorations and updates `last_activity_at` instead of creating duplicate log entries. This can be configured via `prevent_session_restoration_logging` and `session_restoration_window_minutes` settings.

Unsolved:

- None
