#!/bin/bash
# Скрипт для запуска очередей rabbitmq

NB_TASKS=1
SYMFONY_ENV="prod"
ROOT_PATH="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )/../"

BASE="app/console rabbitmq:consumer"
TEXT[0]="jobs"
TEXT[1]="jobs_timeout"
TEXT[2]="mobile"
TEXT[3]="integration_dictionaries"
TEXT[4]="integration_statistics"
TEXT[5]="integration_laws"
TEXT[6]="integration_service"

for text in "${TEXT[@]}"
do

    FULL_TEXT="${BASE} ${text}"
    NB_LAUNCHED=$(ps ax | grep "$FULL_TEXT" | grep -v grep | wc -l)

    TASK="php ${ROOT_PATH}${FULL_TEXT} --env=${SYMFONY_ENV}"

    for (( i=${NB_LAUNCHED}; i<${NB_TASKS}; i++ ))
    do
        echo "$(date +%c) - Launching a new consumer"
        nohup $TASK &
    done

done
