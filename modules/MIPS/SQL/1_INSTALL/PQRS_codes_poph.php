<?php
/* Copyright (C) 2015 - 2017      Suncoast Connection
 * 
 * LICENSE: This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0
 * See the Mozilla Public License for more details. 
 * If a copy of the MPL was not distributed with this file, You can obtain one at https://mozilla.org/MPL/2.0/.
 * 
 * @author  Art Eaton <art@suncoastconnection.com>
 * @author  Bryan lee <leebc@suncoastconnection.com>
 * @package LibreHealthEHR 
 * @link    http://suncoastconnection.com
 * @link    http://librehealth.io
 *
 * Please support this product by sharing your changes with the LibreHealth.io community.
 */


$query =
"DROP TABLE IF EXISTS pqrs_poph;";
sqlStatementNoLog($query);

$query =
"CREATE TABLE IF NOT EXISTS `pqrs_poph` (
id int NOT NULL auto_increment,
type varchar(15),
code varchar(15),
PRIMARY KEY  (`id`)
);";
sqlStatementNoLog($query);

$query =
"INSERT INTO `pqrs_poph` (`type`, `code`) VALUES
('pqrs_0110_a','99201'),
('pqrs_0110_a','99202'),
('pqrs_0110_a','99203'),
('pqrs_0110_a','99204'),
('pqrs_0110_a','99205'),
('pqrs_0110_a','99212'),
('pqrs_0110_a','99213'),
('pqrs_0110_a','99214'),
('pqrs_0110_a','99215'),
('pqrs_0110_a','99241'),
('pqrs_0110_a','99242'),
('pqrs_0110_a','99243'),
('pqrs_0110_a','99244'),
('pqrs_0110_a','99245'),
('pqrs_0110_a','99324'),
('pqrs_0110_a','99325'),
('pqrs_0110_a','99326'),
('pqrs_0110_a','99327'),
('pqrs_0110_a','99328'),
('pqrs_0110_a','99334'),
('pqrs_0110_a','99335'),
('pqrs_0110_a','99336'),
('pqrs_0110_a','99337'),
('pqrs_0110_a','99341'),
('pqrs_0110_a','99342'),
('pqrs_0110_a','99343'),
('pqrs_0110_a','99344'),
('pqrs_0110_a','99345'),
('pqrs_0110_a','99347'),
('pqrs_0110_a','99348'),
('pqrs_0110_a','99349'),
('pqrs_0110_a','99350'),
('pqrs_0110_a','90945'),
('pqrs_0110_a','90947'),
('pqrs_0110_a','90951'),
('pqrs_0110_a','90952'),
('pqrs_0110_a','90953'),
('pqrs_0110_a','90954'),
('pqrs_0110_a','90955'),
('pqrs_0110_a','90956'),
('pqrs_0110_a','90957'),
('pqrs_0110_a','90958'),
('pqrs_0110_a','90959'),
('pqrs_0110_a','90960'),
('pqrs_0110_a','90961'),
('pqrs_0110_a','90962'),
('pqrs_0110_a','90963'),
('pqrs_0110_a','90964'),
('pqrs_0110_a','90965'),
('pqrs_0110_a','90966'),
('pqrs_0110_a','90967'),
('pqrs_0110_a','90968'),
('pqrs_0110_a','90969'),
('pqrs_0110_a','90970'),
('pqrs_0110_a','96160'),
('pqrs_0110_a','96161'),
('pqrs_0110_a','99304'),
('pqrs_0110_a','99305'),
('pqrs_0110_a','99306'),
('pqrs_0110_a','99307'),
('pqrs_0110_a','99308'),
('pqrs_0110_a','99309'),
('pqrs_0110_a','99310'),
('pqrs_0110_a','99315'),
('pqrs_0110_a','99316'),
('pqrs_0110_a','99381'),
('pqrs_0110_a','99382'),
('pqrs_0110_a','99383'),
('pqrs_0110_a','99384'),
('pqrs_0110_a','99385'),
('pqrs_0110_a','99386'),
('pqrs_0110_a','99387'),
('pqrs_0110_a','99391'),
('pqrs_0110_a','99392'),
('pqrs_0110_a','99393'),
('pqrs_0110_a','99394'),
('pqrs_0110_a','99395'),
('pqrs_0110_a','99396'),
('pqrs_0110_a','99397'),
('pqrs_0110_a','99401'),
('pqrs_0110_a','99402'),
('pqrs_0110_a','99403'),
('pqrs_0110_a','99404'),
('pqrs_0110_a','99411'),
('pqrs_0110_a','99412'),
('pqrs_0110_a','99429'),
('pqrs_0110_a','99512'),
('pqrs_0110_a','G0438'),
('pqrs_0110_a','G0439'),
('pqrs_0111_a', '99201'),
('pqrs_0111_a', '99202'),
('pqrs_0111_a', '99203'),
('pqrs_0111_a', '99204'),
('pqrs_0111_a', '99205'),
('pqrs_0111_a', '99212'),
('pqrs_0111_a', '99213'),
('pqrs_0111_a', '99214'),
('pqrs_0111_a', '99215'),
('pqrs_0111_a', '99341'),
('pqrs_0111_a', '99342'),
('pqrs_0111_a', '99343'),
('pqrs_0111_a', '99344'),
('pqrs_0111_a', '99345'),
('pqrs_0111_a', '99347'),
('pqrs_0111_a', '99348'),
('pqrs_0111_a', '99349'),
('pqrs_0111_a', '99350'),
('pqrs_0111_a', 'G0438'),
('pqrs_0111_a', 'G0439'),
('pqrs_0111_a', 'G0402'),
('pqrs_0128_a', '90791'),
('pqrs_0128_a', '90792'),
('pqrs_0128_a', '90832'),
('pqrs_0128_a', '90834'),
('pqrs_0128_a', '90837'),
('pqrs_0128_a', '90839'),
('pqrs_0128_a', '96150'),
('pqrs_0128_a', '96151'),
('pqrs_0128_a', '96152'),
('pqrs_0128_a', '97001'),
('pqrs_0128_a', '97003'),
('pqrs_0128_a', '97802'),
('pqrs_0128_a', '97803'),
('pqrs_0128_a', '99201'),
('pqrs_0128_a', '99202'),
('pqrs_0128_a', '99203'),
('pqrs_0128_a', '99204'),
('pqrs_0128_a', '99205'),
('pqrs_0128_a', '99212'),
('pqrs_0128_a', '99213'),
('pqrs_0128_a', '99214'),
('pqrs_0128_a', '99215'),
('pqrs_0128_a', '99385'),
('pqrs_0128_a', '99386'),
('pqrs_0128_a', '99387'),
('pqrs_0128_a', '99395'),
('pqrs_0128_a', '99396'),
('pqrs_0128_a', '99397'),
('pqrs_0128_a', 'D7140'),
('pqrs_0128_a', 'D7210'),
('pqrs_0128_a', 'G0101'),
('pqrs_0128_a', 'G0108'),
('pqrs_0128_a', 'G0270'),
('pqrs_0128_a', 'G0271'),
('pqrs_0128_a', 'G0402'),
('pqrs_0128_a', 'G0438'),
('pqrs_0128_a', 'G0439'),
('pqrs_0128_a', 'G0447'),
('pqrs_0134_a','59400'),
('pqrs_0134_a','59510'),
('pqrs_0134_a','59610'),
('pqrs_0134_a','59618'),
('pqrs_0134_a','90791'),
('pqrs_0134_a','90792'),
('pqrs_0134_a','90832'),
('pqrs_0134_a','90834'),
('pqrs_0134_a','90837'),
('pqrs_0134_a','92625'),
('pqrs_0134_a','96116'),
('pqrs_0134_a','96118'),
('pqrs_0134_a','96150'),
('pqrs_0134_a','96151'),
('pqrs_0134_a','97165'),
('pqrs_0134_a','97166'),
('pqrs_0134_a','97167'),
('pqrs_0134_a','99201'),
('pqrs_0134_a','99202'),
('pqrs_0134_a','99203'),
('pqrs_0134_a','99204'),
('pqrs_0134_a','99205'),
('pqrs_0134_a','99212'),
('pqrs_0134_a','99213'),
('pqrs_0134_a','99214'),
('pqrs_0134_a','99215'),
('pqrs_0134_a','99384'),
('pqrs_0134_a','99385'),
('pqrs_0134_a','99386'),
('pqrs_0134_a','99387'),
('pqrs_0134_a','99394'),
('pqrs_0134_a','99395'),
('pqrs_0134_a','99396'),
('pqrs_0134_a','99397'),
('pqrs_0134_a','G0101'),
('pqrs_0134_a','G0402'),
('pqrs_0134_a','G0438'),
('pqrs_0134_a','G0439'),
('pqrs_0134_a','G0444'),
('pqrs_0134_a','G0502'),
('pqrs_0134_a','G0503'),
('pqrs_0134_a','G0504'),
('pqrs_0134_a','G0505'),
('pqrs_0134_a','G0507'),
('pqrs_0134_b','F01.51'),
('pqrs_0134_b','F32.0'),
('pqrs_0134_b','F32.1'),
('pqrs_0134_b','F32.2'),
('pqrs_0134_b','F32.3'),
('pqrs_0134_b','F32.4'),
('pqrs_0134_b','F32.5'),
('pqrs_0134_b','F32.89'),
('pqrs_0134_b','F32.9'),
('pqrs_0134_b','F33.0'),
('pqrs_0134_b','F33.1'),
('pqrs_0134_b','F33.2'),
('pqrs_0134_b','F33.3'),
('pqrs_0134_b','F33.40'),
('pqrs_0134_b','F33.41'),
('pqrs_0134_b','F33.42'),
('pqrs_0134_b','F33.8'),
('pqrs_0134_b','F33.9'),
('pqrs_0134_b','F34.1'),
('pqrs_0134_b','F34.81'),
('pqrs_0134_b','F34.89'),
('pqrs_0134_b','F43.21'),
('pqrs_0134_b','F43.23'),
('pqrs_0134_b','F53'),
('pqrs_0134_b','O90.6'),
('pqrs_0134_b','O99.340'),
('pqrs_0134_b','O99.341'),
('pqrs_0134_b','O99.342'),
('pqrs_0134_b','O99.343'),
('pqrs_0134_b','O99.345'),
('pqrs_0134_b','F31.10'),
('pqrs_0134_b','F31.11'),
('pqrs_0134_b','F31.12'),
('pqrs_0134_b','F31.13'),
('pqrs_0134_b','F31.2'),
('pqrs_0134_b','F31.30'),
('pqrs_0134_b','F31.31'),
('pqrs_0134_b','F31.32'),
('pqrs_0134_b','F31.4'),
('pqrs_0134_b','F31.5'),
('pqrs_0134_b','F31.60'),
('pqrs_0134_b','F31.61'),
('pqrs_0134_b','F31.62'),
('pqrs_0134_b','F31.63'),
('pqrs_0134_b','F31.64'),
('pqrs_0134_b','F31.70'),
('pqrs_0134_b','F31.71'),
('pqrs_0134_b','F31.72'),
('pqrs_0134_b','F31.73'),
('pqrs_0134_b','F31.74'),
('pqrs_0134_b','F31.75'),
('pqrs_0134_b','F31.76'),
('pqrs_0134_b','F31.77'),
('pqrs_0134_b','F31.78'),
('pqrs_0134_b','F31.81'),
('pqrs_0134_b','F31.89'),
('pqrs_0134_b','F31.9'),
('pqrs_0134_b','G9717'),
('pqrs_0226_a', '90791'),
('pqrs_0226_a', '90792'),
('pqrs_0226_a', '90832'),
('pqrs_0226_a', '90834'),
('pqrs_0226_a', '90837'),
('pqrs_0226_a', '90845'),
('pqrs_0226_a', '92002'),
('pqrs_0226_a', '92004'),
('pqrs_0226_a', '92012'),
('pqrs_0226_a', '92014'),
('pqrs_0226_a', '96150'),
('pqrs_0226_a', '96151'),
('pqrs_0226_a', '96152'),
('pqrs_0226_a', '97165'),
('pqrs_0226_a', '97166'),
('pqrs_0226_a', '97167'),
('pqrs_0226_a', '97168'),
('pqrs_0226_a', '99201'),
('pqrs_0226_a', '99202'),
('pqrs_0226_a', '99203'),
('pqrs_0226_a', '99204'),
('pqrs_0226_a', '99205'),
('pqrs_0226_a', '99212'),
('pqrs_0226_a', '99213'),
('pqrs_0226_a', '99214'),
('pqrs_0226_a', '99215'),
('pqrs_0226_a', '99341'),
('pqrs_0226_a', '99342'),
('pqrs_0226_a', '99343'),
('pqrs_0226_a', '99344'),
('pqrs_0226_a', '99345'),
('pqrs_0226_a', '99347'),
('pqrs_0226_a', '99348'),
('pqrs_0226_a', '99349'),
('pqrs_0226_a', '99350'),
('pqrs_0226_b', '92521'),
('pqrs_0226_b', '92522'),
('pqrs_0226_b', '92523'),
('pqrs_0226_b', '92524'),
('pqrs_0226_b', '92540'),
('pqrs_0226_b', '92557'),
('pqrs_0226_b', '96160'),
('pqrs_0226_b', '96161'),
('pqrs_0226_b', '92625'),
('pqrs_0226_b', '99385'),
('pqrs_0226_b', '99386'),
('pqrs_0226_b', '99387'),
('pqrs_0226_b', '99395'),
('pqrs_0226_b', '99396'),
('pqrs_0226_b', '99397'),
('pqrs_0226_b', '99401'),
('pqrs_0226_b', '99402'),
('pqrs_0226_b', '99403'),
('pqrs_0226_b', '99404'),
('pqrs_0226_b', '99406'),
('pqrs_0226_b', '99407'),
('pqrs_0226_b', '99411'),
('pqrs_0226_b', '99412'),
('pqrs_0226_b', '99429'),
('pqrs_0226_b', 'G0438'),
('pqrs_0226_b', 'G0439'),
('pqrs_0317_a','90791'),
('pqrs_0317_a','90792'),
('pqrs_0317_a','92002'),
('pqrs_0317_a','92004'),
('pqrs_0317_a','92012'),
('pqrs_0317_a','92014'),
('pqrs_0317_a','99201'),
('pqrs_0317_a','99202'),
('pqrs_0317_a','99203'),
('pqrs_0317_a','99204'),
('pqrs_0317_a','99205'),
('pqrs_0317_a','99212'),
('pqrs_0317_a','99213'),
('pqrs_0317_a','99214'),
('pqrs_0317_a','99281'),
('pqrs_0317_a','99282'),
('pqrs_0317_a','99283'),
('pqrs_0317_a','99284'),
('pqrs_0317_a','99285'),
('pqrs_0317_a','99215'),
('pqrs_0317_a','99304'),
('pqrs_0317_a','99305'),
('pqrs_0317_a','99306'),
('pqrs_0317_a','99307'),
('pqrs_0317_a','99308'),
('pqrs_0317_a','99309'),
('pqrs_0317_a','99310'),
('pqrs_0317_a','99318'),
('pqrs_0317_a','99324'),
('pqrs_0317_a','99325'),
('pqrs_0317_a','99326'),
('pqrs_0317_a','99327'),
('pqrs_0317_a','99328'),
('pqrs_0317_a','99334'),
('pqrs_0317_a','99335'),
('pqrs_0317_a','99336'),
('pqrs_0317_a','99337'),
('pqrs_0317_a','99341'),
('pqrs_0317_a','99342'),
('pqrs_0317_a','99343'),
('pqrs_0317_a','99344'),
('pqrs_0317_a','99345'),
('pqrs_0317_a','99347'),
('pqrs_0317_a','99348'),
('pqrs_0317_a','99349'),
('pqrs_0317_a','99350'),
('pqrs_0317_a','99385'),
('pqrs_0317_a','99386'),
('pqrs_0317_a','99387'),
('pqrs_0317_a','99395'),
('pqrs_0317_a','99396'),
('pqrs_0317_a','99397'),
('pqrs_0317_a','D7140'),
('pqrs_0317_a','D7210'),
('pqrs_0317_a','G0101'),
('pqrs_0317_a','G0402'),
('pqrs_0317_a','G0438'),
('pqrs_0317_a','G0439'),
('pqrs_0394_a', '99201'),
('pqrs_0394_a', '99202'),
('pqrs_0394_a', '99203'),
('pqrs_0394_a', '99204'),
('pqrs_0394_a', '99205'),
('pqrs_0394_a', '99211'),
('pqrs_0394_a', '99212'),
('pqrs_0394_a', '99213'),
('pqrs_0394_a', '99214'),
('pqrs_0394_a', '99215'),
('pqrs_0394_a', '99324'),
('pqrs_0394_a', '99325'),
('pqrs_0394_a', '99326'),
('pqrs_0394_a', '99327'),
('pqrs_0394_a', '99328'),
('pqrs_0394_a', '99334'),
('pqrs_0394_a', '99335'),
('pqrs_0394_a', '99336'),
('pqrs_0394_a', '99337'),
('pqrs_0394_a', '99341'),
('pqrs_0394_a', '99342'),
('pqrs_0394_a', '99343'),
('pqrs_0394_a', '99344'),
('pqrs_0394_a', '99345'),
('pqrs_0394_a', '99347'),
('pqrs_0394_a', '99348'),
('pqrs_0394_a', '99349'),
('pqrs_0394_a', '99350'),
('pqrs_0394_a', 'G0402'),
('pqrs_0402_a', '90791'),
('pqrs_0402_a', '90792'),
('pqrs_0402_a', '90832'),
('pqrs_0402_a', '90834'),
('pqrs_0402_a', '90837'),
('pqrs_0402_a', '90839'),
('pqrs_0402_a', '90845'),
('pqrs_0402_a', '92002'),
('pqrs_0402_a', '92004'),
('pqrs_0402_a', '92012'),
('pqrs_0402_a', '92014'),
('pqrs_0402_a', '96150'),
('pqrs_0402_a', '96151'),
('pqrs_0402_a', '96152'),
('pqrs_0402_a', '97003'),
('pqrs_0402_a', '97004'),
('pqrs_0402_a', '99201'),
('pqrs_0402_a', '99202'),
('pqrs_0402_a', '99203'),
('pqrs_0402_a', '99204'),
('pqrs_0402_a', '99205'),
('pqrs_0402_a', '99212'),
('pqrs_0402_a', '99213'),
('pqrs_0402_a', '99214'),
('pqrs_0402_a', '99215'),
('pqrs_0402_a', '99406'),
('pqrs_0402_a', '99407'),
('pqrs_0402_a', 'G0438'),
('pqrs_0402_a', 'G0439'),

('pqrs_0431_a', '90791'),
('pqrs_0431_a', '90792'),
('pqrs_0431_a', '90832'),
('pqrs_0431_a', '90834'),
('pqrs_0431_a', '90837'),
('pqrs_0431_a', '90845'),
('pqrs_0431_a', '96150'),
('pqrs_0431_a', '96151'),
('pqrs_0431_a', '96152'),
('pqrs_0431_a', '97165'),
('pqrs_0431_a', '97166'),
('pqrs_0431_a', '97167'),
('pqrs_0431_a', '97168'),
('pqrs_0431_a', '97802'),
('pqrs_0431_a', '97803'),
('pqrs_0431_a', '97804'),
('pqrs_0431_a', '99201'),
('pqrs_0431_a', '99202'),
('pqrs_0431_a', '99203'),
('pqrs_0431_a', '99204'),
('pqrs_0431_a', '99205'),
('pqrs_0431_a', '99212'),
('pqrs_0431_a', '99213'),
('pqrs_0431_a', '99214'),
('pqrs_0431_a', '99215'),
('pqrs_0431_a', 'G0270'),
('pqrs_0431_a', 'G0271'),
('pqrs_0431_b', '96160'),
('pqrs_0431_b', '96161'),
('pqrs_0431_b', '99385'),
('pqrs_0431_b', '99386'),
('pqrs_0431_b', '99387'),
('pqrs_0431_b', '99395'),
('pqrs_0431_b', '99396'),
('pqrs_0431_b', '99397'),
('pqrs_0431_b', '99401'),
('pqrs_0431_b', '99402'),
('pqrs_0431_b', '99403'),
('pqrs_0431_b', '99404'),
('pqrs_0431_b', '99411'),
('pqrs_0431_b', '99412'),
('pqrs_0431_b', '99429'),
('pqrs_0431_b', 'G0438'),
('pqrs_0431_b', 'G0439');";
sqlStatementNoLog($query);
