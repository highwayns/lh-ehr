<?php
/** @package LibreHealth EHR::Model::DAO */
/**
 *
 * Copyright (C) 2016-2017 Jerry Padgett <sjpadgett@gmail.com>
 *
 * LICENSE: This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0
 * See the Mozilla Public License for more details.
 * If a copy of the MPL was not distributed with this file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package LibreHealth EHR
 * @author Jerry Padgett <sjpadgett@gmail.com>
 * @link http://librehealth.io
 */

/** import supporting libraries */
require_once("verysimple/Phreeze/Phreezable.php");
require_once("OnsitePortalActivityMap.php");

/**
 * OnsitePortalActivityDAO provides object-oriented access to the onsite_portal_activity table.  This
 * class is automatically generated by ClassBuilder.
 *
 * WARNING: THIS IS AN AUTO-GENERATED FILE
 *
 * This file should generally not be edited by hand except in special circumstances.
 * Add any custom business logic to the Model class which is extended from this DAO class.
 * Leaving this file alone will allow easy re-generation of all DAOs in the event of schema changes
 *
 * @package LibreHealth EHR::Model::DAO
 * @author ClassBuilder
 * @version 1.0
 */
class OnsitePortalActivityDAO extends Phreezable
{
    /** @var int */
    public $Id;

    /** @var date */
    public $Date;

    /** @var int */
    public $PatientId;

    /** @var string */
    public $Activity;

    /** @var int */
    public $RequireAudit;

    /** @var string */
    public $PendingAction;

    /** @var string */
    public $ActionTaken;

    /** @var string */
    public $Status;

    /** @var longtext */
    public $Narrative;

    /** @var longtext */
    public $TableAction;

    /** @var longtext */
    public $TableArgs;

    /** @var int */
    public $ActionUser;

    /** @var date */
    public $ActionTakenTime;

    /** @var longtext */
    public $Checksum;



}
?>