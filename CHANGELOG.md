# Changelog

All notable changes to `laravel-gdpr-consent-database` will be documented in this file.

## v1.0.3 - 2025-06-14

Compatibility with MySql, PostgreSQL, sqlite

## v1.0.1 - 2025-06-14

This release fixes the database migration issue and the readme.md file instructions

## v1.0.0 - 2025-06-14

🎉 First stable release of `laravel-gdpr-consent-database`

This package provides a simple and customizable solution for storing and managing user consent records in compliance with GDPR regulations, using Laravel's Eloquent ORM and migrations.

### ✅ Features

- Store GDPR consent records in the database
- Associate consents with users or anonymous sessions
- Track timestamp and purpose of each consent
- Artisan command for cleanup of expired consents
- Middleware example for user consent enforcement
- Compatible with Laravel 11+
