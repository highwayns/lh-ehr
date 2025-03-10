<?php
/**
 *
 * Patient Portal Lab Results
 *
 * Copyright (C) 2016-2017 Jerry Padgett <sjpadgett@gmail.com>
 * Copyright (C) 2011 Cassian LUP <cassi.lup@gmail.com>
 *
 * LICENSE: This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://opensource.org/licenses/gpl-license.php>;.
 *
 * LICENSE: This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0
 * See the Mozilla Public License for more details.
 * If a copy of the MPL was not distributed with this file, You can obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package LibreHealth EHR
 * @author Cassian LUP <cassi.lup@gmail.com>
 * @author Jerry Padgett <sjpadgett@gmail.com>
 * @link http://librehealth.io
 *
 * Please help the overall project by sending changes you make to the author and to the LibreEHR community.
 *
 */

        require_once("verify_session.php");


        require_once('../library/options.inc.php');

    $selects =
    "po.procedure_order_id, po.date_ordered, pc.procedure_order_seq, " .
    "pt1.procedure_type_id AS order_type_id, pc.procedure_name, " .
    "pr.procedure_report_id, pr.date_report, pr.date_collected, pr.specimen_num, " .
    "pr.report_status, pr.review_status";

  $joins =
    "JOIN procedure_order_code AS pc ON pc.procedure_order_id = po.procedure_order_id " .
    "LEFT JOIN procedure_type AS pt1 ON pt1.lab_id = po.lab_id AND pt1.procedure_code = pc.procedure_code " .
    "LEFT JOIN procedure_report AS pr ON pr.procedure_order_id = po.procedure_order_id AND " .
    "pr.procedure_order_seq = pc.procedure_order_seq";

  $orderby =
    "po.date_ordered, po.procedure_order_id, " .
    "pc.procedure_order_seq, pr.procedure_report_id";

  $where = "1 = 1";
        
  $res = sqlStatement("SELECT $selects " .
      "FROM procedure_order AS po $joins " .
      "WHERE po.patient_id = ? AND $where " .
      "ORDER BY $orderby", array($pid));

    if(sqlNumRows($res)>0)
    {
        ?>
        <table class="table table-striped table-condensed table-bordered">
            <tr class="header">
                <th><?php echo xlt('Order Date'); ?></th>
                <th><?php echo xlt('Order Name'); ?></th>
                <th><?php echo xlt('Result Name'); ?></th>
                <th><?php echo xlt('Abnormal'); ?></th>
                <th><?php echo xlt('Value'); ?></th>
                <th><?php echo xlt('Range'); ?></th>
                <th><?php echo xlt('Units'); ?></th>
                <th><?php echo xlt('Result Status'); ?></th>
                <th><?php echo xlt('Report Status'); ?></th>
            </tr>
        <?php
        $even=false;

        while ($row = sqlFetchArray($res)) {
        $order_type_id  = empty($row['order_type_id'      ]) ? 0 : ($row['order_type_id' ] + 0);
        $report_id      = empty($row['procedure_report_id']) ? 0 : ($row['procedure_report_id'] + 0);

        $selects = "pt2.procedure_type, pt2.procedure_code, pt2.units AS pt2_units, " .
          "pt2.range AS pt2_range, pt2.procedure_type_id AS procedure_type_id, " .
          "pt2.name AS name, pt2.description, pt2.seq AS seq, " .
          "ps.procedure_result_id, ps.result_code AS result_code, ps.result_text, ps.abnormal, ps.result, " .
          "ps.range, ps.result_status, ps.facility, ps.comments, ps.units, ps.comments";

        // procedure_type_id for order:
        $pt2cond = "pt2.parent = $order_type_id AND " .
          "(pt2.procedure_type LIKE 'res%' OR pt2.procedure_type LIKE 'rec%')";

        // pr.procedure_report_id or 0 if none:
        $pscond = "ps.procedure_report_id = $report_id";

        $joincond = "ps.result_code = pt2.procedure_code";

        // This union emulates a full outer join. The idea is to pick up all
        // result types defined for this order type, as well as any actual
        // results that do not have a matching result type.
        $query = "(SELECT $selects FROM procedure_type AS pt2 " .
          "LEFT JOIN procedure_result AS ps ON $pscond AND $joincond " .
          "WHERE $pt2cond" .
          ") UNION (" .
          "SELECT $selects FROM procedure_result AS ps " .
          "LEFT JOIN procedure_type AS pt2 ON $pt2cond AND $joincond " .
          "WHERE $pscond) " .
          "ORDER BY seq, name, procedure_type_id, result_code";

        $rres = sqlStatement($query);
        while ($rrow = sqlFetchArray($rres)) {

            if ($even) {
                $class="class1_even";
                $even=false;
            } else {
                $class="class1_odd";
                $even=true;
            }
            $date=explode('-',$row['date_ordered']);
            echo "<tr class='".$class."'>";
            echo "<td>".text($date[1]."/".$date[2]."/".$date[0])."</td>";
            echo "<td>".text($row['procedure_name'])."</td>";
            echo "<td>".text($rrow['name'])."</td>";
                        echo "<td>".generate_display_field(array('data_type'=>'1','list_id'=>'proc_res_abnormal'),$rrow['abnormal'])."</td>";
            echo "<td>".text($row['result'])."</td>";
            echo "<td>".text($rrow['pt2_range'])."</td>";
                        echo "<td>".generate_display_field(array('data_type'=>'1','list_id'=>'proc_unit'),$rrow['pt2_units'])."</td>";
                        echo "<td>".generate_display_field(array('data_type'=>'1','list_id'=>'proc_res_status'),$rrow['result_status'])."</td>";
                        echo "<td>".generate_display_field(array('data_type'=>'1','list_id'=>'proc_rep_status'),$row['report_status'])."</td>";
            echo "</tr>";

      }

     }

        echo "</table>";
    }
    else
    {
        echo xlt("No Results");
    }
?>
