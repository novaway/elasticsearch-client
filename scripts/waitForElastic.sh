#!/usr/bin/env bash
echo -n "Waiting for elasticsearch to be ready ..."

code=`curl --write-out %{http_code} --silent --output /dev/null http://localhost:9200/_cluster/health`;
iteration=0;

while [ $code -ne 200 ] ; do
    sleep 1;
    /bin/echo -n ".";

    code=`curl --write-out %{http_code} --silent --output /dev/null http://localhost:9200/_cluster/health`;
    iteration=`expr $iteration + 1`;

    if [ $iteration -gt 180 ]; then
        echo "TIMEOUT !"
        exit 1;
    fi
done;

echo " READY !" ;
exit 0;
