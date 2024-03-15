#!/usr/bin/env bash

docker build -t docker.tgnb.cc/bag/nginx-service:1.0 .
docker push docker.tgnb.cc/bag/nginx-service:1.0