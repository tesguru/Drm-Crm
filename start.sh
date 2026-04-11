#!/bin/bash
echo "🚀 Starting Domain Outreach System..."
pkill -f "artisan serve" 2>/dev/null
pkill -f "artisan queue" 2>/dev/null

# Resets ONLY the daily counter — nothing else
php artisan tinker --execute="
    App\Models\GmailAccount::query()
        ->update(['daily_sent' => 0]);
    echo 'Daily counts reset';
"

php artisan serve &
echo "✅ Server started — http://localhost:8000"

php artisan queue:work --tries=3 &
echo "✅ Queue worker started"

echo ""
echo "🌐 Open: http://localhost:8000"
echo "Press Ctrl+C to stop"

wait