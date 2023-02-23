<?php

namespace Dux\Websocket\Handler;

enum EventEnum:string
{
    case ONLINE = 'online';
    case OFFLINE = 'offline';
    case MESSAGE = 'message';
}