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

    // on_delete_dr
    protected $connection = 'prcpwb';

    public function up(): void
    {
        DB::connection($this->connection)->statement("
            CREATE OR REPLACE FUNCTION FNPRCPWBONDELETEDAILYREQ()
            RETURNS TRIGGER AS \$\$
            BEGIN
                INSERT INTO \"PRCPWB_TRHDELETEDDAILYREQUESTS\" (
                    \"VVENDORNO\",
                    \"VPARTNO\",
                    \"VPARTDESCRIPTION\",
                    \"DWANTEDRECEIPTDATE\",
                    \"DPROPOSEDWANTEDRECEIPTDATE\",
                    \"VTIME\",
                    \"IQUANTITY\",
                    \"IQUANTITYCONFIRMATION\",
                    \"IQUANTITYACTUAL\",
                    \"VSTATUS\",
                    \"VDELIVERYNOTENO\",
                    \"VPONO\",
                    \"VDAILYREQNO\",
                    \"IREVNO\",
                    \"VFORECAST\",
                    \"IMSPERIOD\",
                    \"IMSYEAR\",
                    \"VUNITMEAS\",
                    \"VDEDICATEDLOCATION\",
                    \"VPROCCONTACT\",
                    \"VCREA\",
                    \"DCREA\",
                    \"VMODI\",
                    \"DMODI\",
                    \"DDELETE\",
                    \"DDELETED\"
                ) VALUES (
                    OLD.\"VVENDORNO\",
                    OLD.\"VPARTNO\",
                    OLD.\"VPARTDESCRIPTION\",
                    OLD.\"DWANTEDRECEIPTDATE\",
                    OLD.\"DPROPOSEDWANTEDRECEIPTDATE\",
                    OLD.\"VTIME\",
                    OLD.\"IQUANTITY\",
                    OLD.\"IQUANTITYCONFIRMATION\",
                    OLD.\"IQUANTITYACTUAL\",
                    OLD.\"VSTATUS\",
                    OLD.\"VDELIVERYNOTENO\",
                    OLD.\"VPONO\",
                    OLD.\"VDAILYREQNO\",
                    OLD.\"IREVNO\",
                    OLD.\"VFORECAST\",
                    OLD.\"IMSPERIOD\",
                    OLD.\"IMSYEAR\",
                    OLD.\"VUNITMEAS\",
                    OLD.\"VDEDICATEDLOCATION\",
                    OLD.\"VPROCCONTACT\",
                    OLD.\"VCREA\",
                    OLD.\"DCREA\",
                    OLD.\"VMODI\",
                    OLD.\"DMODI\",
                    OLD.\"DDELETE\",
                    NOW()
                );
                RETURN OLD;
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        // Baru buat trigger-nya yang memanggil function di atas
        DB::connection($this->connection)->statement("
            CREATE OR REPLACE TRIGGER TR_BF00_DELETE
            BEFORE DELETE ON \"PRCPWB_TRHDAILYREQUESTS\"
            FOR EACH ROW
            EXECUTE FUNCTION FNPRCPWBONDELETEDAILYREQ();
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::connection($this->connection)->statement('DROP TRIGGER IF EXISTS TR_BF00_DELETE ON "PRCPWB_TRHDAILYREQUESTS"');
        DB::connection($this->connection)->statement('DROP FUNCTION IF EXISTS FNPRCPWBONDELETEDAILYREQ');
    }
};
