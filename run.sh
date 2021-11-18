#!/bin/bash
MODE="${1:-dev}"
PORT="${2:-8001}"
cd webapp && mode="$MODE" php -S localhost:"$PORT" server.php