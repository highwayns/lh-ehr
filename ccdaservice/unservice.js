/**
 *
 * Copyright (C) 2016-2017 Jerry Padgett <sjpadgett@gmail.com>
 *
 * LICENSE: This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package LibreHEalth EHR
 * @author Jerry Padgett <sjpadgett@gmail.com>
 * @link http://librehealth.io
 */
var isWin = /^win/.test(process.platform);
if( isWin ){
    var Service = require('node-windows').Service;
}
else{
    var Service = require('node-linux').Service;
}
var svc = new Service({
  name:'CCDA Service',
  script: require('path').join(__dirname,'serveccda.njs')
});
svc.on('uninstall',function(){
    console.log('Uninstall complete.');
    if(!isWin)
        console.log('The service exists: ',svc.exists());
    else
        console.log('The service exists: ',svc.exists);
  });

svc.uninstall();