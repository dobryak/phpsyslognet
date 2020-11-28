<?php
namespace SyslogNet;

/**
 * @author Dobriakov A.
 * @copyright Copyright (c) 2020 Dobriakov Anton
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
class Facility
{
    const KERN_MSG        = 0;
    const USR_MSG         = 1;
    const MAIL_SYS        = 2;
    const DAEMON          = 3;
    const AUTH_MSG        = 4;
    const SYSLOGD         = 5;
    const PRINTER         = 6;
    const NET_NEWS        = 7;
    const UUCP            = 8;
    const CLOCK_DAEMON    = 9;
    const AUTHPRIV_MSG    = 10;
    const FTP             = 11;
    const NTP             = 12;
    const AUDIT           = 13;
    const ALERT           = 14;
    const CLOCK_DAEMON_N2 = 15;
    const LOCAL_0         = 16;
    const LOCAL_1         = 17;
    const LOCAL_2         = 18;
    const LOCAL_3         = 19;
    const LOCAL_4         = 20;
    const LOCAL_5         = 21;
    const LOCAL_6         = 22;
    const LOCAL_7         = 23;
}
