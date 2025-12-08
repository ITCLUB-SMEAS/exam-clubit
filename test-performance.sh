#!/bin/bash

echo "=== CBT Performance Test ==="
echo ""

echo "1. OPcache Status:"
php -r "
\$status = opcache_get_status();
echo 'Memory Used: ' . round(\$status['memory_usage']['used_memory']/1024/1024, 2) . ' MB' . PHP_EOL;
echo 'Hit Rate: ' . round(\$status['opcache_statistics']['opcache_hit_rate'], 2) . '%' . PHP_EOL;
echo 'Cached Scripts: ' . \$status['opcache_statistics']['num_cached_scripts'] . PHP_EOL;
echo 'JIT Enabled: ' . (opcache_get_configuration()['directives']['opcache.jit'] ? 'Yes' : 'No') . PHP_EOL;
"
echo ""

echo "2. Redis Status:"
redis-cli INFO stats | grep -E "keyspace_hits|keyspace_misses" | head -2
redis-cli INFO memory | grep "used_memory_human"
echo ""

echo "3. Page Load Times (5 requests):"
for i in {1..5}; do
    curl -s -o /dev/null -w "Request $i: %{time_total}s\n" https://exam.clubit.id
done
echo ""

echo "4. Cache Status:"
cd /var/www/clubit.id/exam
php artisan tinker --execute="
echo 'Config Cached: ' . (app()->configurationIsCached() ? 'Yes' : 'No') . PHP_EOL;
echo 'Routes Cached: ' . (app()->routesAreCached() ? 'Yes' : 'No') . PHP_EOL;
"
echo ""

echo "=== Test Complete ==="
