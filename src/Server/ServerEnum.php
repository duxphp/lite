<?php

namespace Dux\Server;

enum ServerEnum: string
{
    case FPM = "FPM";
    case WORKERMAN = "workerman";
}
