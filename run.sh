#!/bin/bash
# ====================================================================
# RTIH Task Tracker - Quick Start Server Script
# Organization: Ratan Tata Innovation Hub Amaravati Foundation
# Technical Assignment: Intern - IT
# ====================================================================

# Resolve PHP binary
if command -v php >/dev/null 2>&1; then
    PHP_BIN="php"
elif [ -f "$HOME/.local/bin/php" ]; then
    PHP_BIN="$HOME/.local/bin/php"
elif [ -f "/tmp/php-bin/php" ]; then
    PHP_BIN="/tmp/php-bin/php"
else
    echo "❌ Error: PHP is not installed or not in PATH."
    echo "Please install PHP 8.x or run via XAMPP / MAMP / Apache."
    exit 1
fi

PORT=8000
HOST="localhost"

echo "============================================================"
echo "  🚀 Task Tracker - Development Server"
echo "============================================================"
echo ""
echo "  📍 Local Server URL : http://$HOST:$PORT"
echo ""
echo "  🔑 Pre-configured Test Accounts:"
echo "     Standard User : user@rtih.local   | Password: User@123"
echo "     Administrator : admin@rtih.local  | Password: Admin@123"
echo ""
echo "  🗄️ Database:"
echo "     MySQL Export  : database.sql"
echo "     Default Host  : 127.0.0.1:3306 (Auto-fallback to SQLite enabled)"
echo ""
echo "  Press Ctrl+C to stop the development server."
echo "============================================================"
echo ""

exec "$PHP_BIN" -S "$HOST:$PORT"
