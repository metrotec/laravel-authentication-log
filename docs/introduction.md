---
title: Introduction
weight: 1
---

<section class="article_badges">
    <a href="https://packagist.org/packages/rappasoft/laravel-authentication-log"><img src="https://img.shields.io/packagist/v/rappasoft/laravel-authentication-log.svg?style=flat-square" alt="Latest Version on Packagist"></a>
    <a href="https://github.com/rappasoft/laravel-authentication-log/actions/workflows/php-cs-fixer.yml"><img src="https://github.com/rappasoft/laravel-authentication-log/actions/workflows/php-cs-fixer.yml/badge.svg" alt="Styling"></a>
    <a href="https://github.com/rappasoft/laravel-authentication-log/actions/workflows/run-tests.yml"><img src="https://github.com/rappasoft/laravel-authentication-log/actions/workflows/run-tests.yml/badge.svg" alt="Tests"></a>
    <a href="https://packagist.org/packages/rappasoft/laravel-authentication-log"><img src="https://img.shields.io/packagist/dt/rappasoft/laravel-authentication-log.svg?style=flat-square" alt="Total Downloads"></a>
</section>

Welcome to the [Laravel Authentication Log](https://github.com/rappasoft/laravel-authentication-log) documentation!

I will do my best to document all features and configurations of this plugin.

Laravel Authentication Log is a comprehensive package which tracks your user's authentication information such as login/logout time, IP, Browser, Location, Device Fingerprint, etc. It sends out notifications via mail, slack, or SMS for new devices and failed logins, detects suspicious activity, provides session management, and much more.

## Features

### Core Features
- ✅ **Authentication Logging** - Tracks all login/logout attempts with IP, user agent, location, and timestamps
- ✅ **Device Fingerprinting** - Reliable device identification using SHA-256 hashing
- ✅ **New Device Detection** - Automatically detects and notifies users of new device logins
- ✅ **Failed Login Tracking** - Logs and optionally notifies users of failed login attempts
- ✅ **Location Tracking** - Optional GeoIP integration for location data

### Advanced Features
- 🔒 **Suspicious Activity Detection** - Automatically detects multiple failed logins, rapid location changes, and unusual login times
- 📊 **Statistics & Insights** - Get comprehensive login statistics including total logins, failed attempts, unique devices, and more
- 🔐 **Session Management** - View active sessions, revoke specific sessions, or logout all other devices
- 🛡️ **Device Trust Management** - Mark devices as trusted, manage device names, and require trusted devices for sensitive actions
- ⚡ **Rate Limiting** - Prevents notification spam with configurable rate limits
- 🔔 **Webhook Support** - Send webhooks to external services for authentication events
- 📤 **Export Functionality** - Export authentication logs to CSV or JSON format
- 🎯 **Query Scopes** - Powerful query scopes for filtering logs (successful, failed, suspicious, recent, by IP, by device, etc.)
- 🚦 **Middleware** - Protect routes with trusted device middleware
