<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

    // view_sim_get_data_stockvendor_filter
    protected $connection = 'prcpwb';

    public function up(): void
    {
        DB::connection($this->connection)->statement("
            CREATE OR REPLACE VIEW VW_PRCPWB_VENDORFILTER AS
            SELECT
                st.\"VVENDORNO\"                             AS vendor_no,
                vn.\"VVENDORNAME\"                          AS vendor_name,
                st.\"VPARTNO\"                              AS part_no,
                dr.\"VPARTDESCRIPTION\"                     AS description,
                st.\"EQTYONHAND\"                           AS qty_on_hand,
                dr.\"VUNITMEAS\"                            AS unit_meas,
                st.\"DUPLOADDATE\"                          AS upload_date,
                SUM(dr.\"IQUANTITY\")                       AS qty_dr,

                (
                    SELECT COALESCE(SUM(y.\"IQUANTITY\"), 0)
                    FROM \"PRCPWB_TRHDAILYREQUESTS\" y
                    WHERE y.\"VVENDORNO\" = st.\"VVENDORNO\"
                      AND y.\"VPARTNO\"  = st.\"VPARTNO\"
                      AND y.\"VSTATUS\" <> 'Received'
                      AND y.\"DWANTEDRECEIPTDATE\" BETWEEN DATE_TRUNC('month', NOW())
                          AND (dr.\"DWANTEDRECEIPTDATE\" - INTERVAL '1 day')
                )                                           AS bal,

                CASE
                    WHEN COALESCE(
                        COALESCE(st.\"EQTYONHAND\", 0) /
                        NULLIF(SUM(dr.\"IQUANTITY\") + (
                            SELECT COALESCE(SUM(y.\"IQUANTITY\"), 0)
                            FROM \"PRCPWB_TRHDAILYREQUESTS\" y
                            WHERE y.\"VVENDORNO\" = st.\"VVENDORNO\"
                              AND y.\"VPARTNO\"  = st.\"VPARTNO\"
                              AND y.\"VSTATUS\" <> 'Received'
                              AND y.\"DWANTEDRECEIPTDATE\" BETWEEN DATE_TRUNC('month', NOW())
                                  AND (dr.\"DWANTEDRECEIPTDATE\" - INTERVAL '1 day')
                        ), 0)
                    , 3) >= 3 THEN 'Green'
                    WHEN COALESCE(
                        COALESCE(st.\"EQTYONHAND\", 0) /
                        NULLIF(SUM(dr.\"IQUANTITY\") + (
                            SELECT COALESCE(SUM(y.\"IQUANTITY\"), 0)
                            FROM \"PRCPWB_TRHDAILYREQUESTS\" y
                            WHERE y.\"VVENDORNO\" = st.\"VVENDORNO\"
                              AND y.\"VPARTNO\"  = st.\"VPARTNO\"
                              AND y.\"VSTATUS\" <> 'Received'
                              AND y.\"DWANTEDRECEIPTDATE\" BETWEEN DATE_TRUNC('month', NOW())
                                  AND (dr.\"DWANTEDRECEIPTDATE\" - INTERVAL '1 day')
                        ), 0)
                    , 3) >= 1 THEN 'Yellow'
                    ELSE 'Red'
                END                                         AS judgment,

                TO_CHAR(dr.\"DWANTEDRECEIPTDATE\", 'DD/MM/YYYY') AS wanted_receipt_date,
                st.\"VREMARK\"                              AS remark

            FROM \"PRCPWB_MSHSTOCKVENDORS\" st
            -- INNER JOIN (beda dengan BAL yang LEFT JOIN)
            JOIN \"PRCPWB_TRHDAILYREQUESTS\" dr
                ON  dr.\"VVENDORNO\" = st.\"VVENDORNO\"
                AND dr.\"VPARTNO\"   = st.\"VPARTNO\"
                AND TO_CHAR(st.\"DUPLOADDATE\", 'DD/MM/YYYY') = TO_CHAR(dr.\"DWANTEDRECEIPTDATE\", 'DD/MM/YYYY')
            JOIN \"PRCPWB_MSHVENDORS\" vn
                ON st.\"VVENDORNO\" = vn.\"VVENDORNO\"

            -- Tidak ada filter WHERE upload_date

            GROUP BY
                st.\"VVENDORNO\", st.\"VPARTNO\", st.\"DUPLOADDATE\",
                vn.\"VVENDORNAME\", dr.\"VPARTDESCRIPTION\",
                st.\"EQTYONHAND\", dr.\"VUNITMEAS\", st.\"VREMARK\",
                dr.\"DWANTEDRECEIPTDATE\"

            ORDER BY st.\"DUPLOADDATE\" DESC
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::connection($this->connection)->statement('DROP VIEW IF EXISTS VW_PRCPWB_VENDORFILTER');
    }
};
