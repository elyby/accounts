#!/usr/bin/env sh

result="upstream php {"

for x in $(echo $PHP_SERVERS | tr "," "\n"); do
    parts=$(echo $x | tr "x" "\n")
    host=$(echo $parts | awk '{print $1}')
    weight=$(echo $parts | awk '{print $2}')

    result="$result\n    server $host weight=${weight:-1};"
done

result="$result\n}"

echo -e $result > /etc/nginx/conf.d/upstream.conf
