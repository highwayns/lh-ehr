CREATE TABLE IF NOT EXISTS `form_vitals` (
`id`                bigint(20)      NOT NULL auto_increment,
`date`              datetime        default NULL,
`pid`               bigint(20)      default 0,
`user`              varchar(255)    default NULL,
`groupname`         varchar(255)    default NULL,
`authorized`        tinyint(4)      default 0,
`activity`          tinyint(4)      default 0,
`bps`               varchar(40)     default 0,
`bpd`               varchar(40)     default 0,
`weight`            FLOAT(5,2)      default 0,
`height`            FLOAT(5,2)      default 0,
`temperature`       FLOAT(5,2)      default 0,
`temp_method`       VARCHAR(255)    default NULL,
`pulse`             FLOAT(5,2)      default 0,
`respiration`       FLOAT(5,2)      default 0,
`note`              VARCHAR(255)    default NULL,
`BMI`               FLOAT(4,1)      default 0,
`BMI_status`        VARCHAR(255)    default NULL,
`waist_circ`        FLOAT(5,2)      default 0,
`head_circ`         FLOAT(4,2)      default 0,
`oxygen_saturation` FLOAT(5,2)      default 0,
PRIMARY KEY (id)
)ENGINE=InnoDB;
