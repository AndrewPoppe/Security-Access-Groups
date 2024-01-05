<?php

namespace YaleREDCap\SecurityAccessGroups;

class RightsUtilities
{
    private SecurityAccessGroups $module;

    public function __construct(SecurityAccessGroups $module)
    {
        $this->module = $module;
    }


    // E.g., from ["export-form-form1"=>"1", "export-form-form2"=>"1"] to "[form1,1][form2,1]"
    private static function convertExportRightsArrayToString($fullRightsArray)
    {
        $result = "";
        foreach ( $fullRightsArray as $key => $value ) {
            if ( substr_compare($key, 'export-form-', 0, strlen('export-form-')) === 0 ) {
                $formName = str_replace('export-form-', '', $key);
                $result .= '[' . $formName . ',' . $value . ']';
            }
        }
        return $result;
    }

    // E.g., from ["form-form1"=>"1", "form-form2"=>"1"] to "[form1,1][form2,1]"
    private static function convertDataEntryRightsArrayToString($fullRightsArray)
    {
        $result = "";
        foreach ( $fullRightsArray as $key => $value ) {
            if ( substr_compare($key, 'form-', 0, strlen('form-')) === 0 && substr_compare($key, 'form-editresp-', 0, strlen('form-editresp-')) !== 0 ) {
                $formName = str_replace('form-', '', $key);

                if ( $fullRightsArray['form-editresp-' . $formName] === 'on' ) {
                    $value = '3';
                }

                $result .= '[' . $formName . ',' . $value . ']';
            }
        }
        return $result;
    }

    // E.g., from "[form1,1][form2,1]" to ["export-form-form1"=>"1", "export-form-form2"=>"1"]
    public static function convertExportRightsStringToArray($fullRightsString)
    {
        $raw    = \UserRights::convertFormRightsToArray($fullRightsString);
        $result = [];
        foreach ( $raw as $key => $value ) {
            $result['export-form-' . $key] = $value;
        }
        return $result;
    }

    // E.g., from "[form1,1][form2,1]" to ["form-form1"=>"1", "form-form2"=>"1"]
    public static function convertDataEntryRightsStringToArray($fullRightsString)
    {
        $raw    = \UserRights::convertFormRightsToArray($fullRightsString);
        $result = [];
        foreach ( $raw as $key => $value ) {
            if ( $value == 3 ) {
                $result['form-' . $key]          = 2;
                $result['form-editresp-' . $key] = 'on';
            } else {
                $result['form-' . $key] = $value;
            }
        }
        return $result;
    }

    private static function convertDoubleData($rights)
    {
        // 0: Reviewer
        // 1: Person 1
        // 2: Person 2
        $value = $rights['double_data'];
        if ( isset($value) ) {
            $rights['double_data_reviewer'] = intval($value) === 0 ? 1 : 0;
            $rights['double_data_person']   = intval($value) > 0 ? 1 : 0;
        }
        return $rights;
    }



    private static function convertDataQualityResolution($rights)
    {
        // 0: no access
        // 1: view only
        // 4: open queries only
        // 2: respond only to opened queries
        // 5: open and respond to queries
        // 3: open, close, and respond to queries
        $value = $rights['data_quality_resolution'];
        if ( $value ) {
            $rights['data_quality_resolution_view']    = intval($value) > 0 ? 1 : 0;
            $rights['data_quality_resolution_open']    = in_array(intval($value), [ 3, 4, 5 ], true) ? 1 : 0;
            $rights['data_quality_resolution_respond'] = in_array(intval($value), [ 2, 3, 5 ], true) ? 1 : 0;
            $rights['data_quality_resolution_close']   = intval($value) === 3 ? 1 : 0;
        }
        return $rights;
    }

    public static function convertPermissions(string $permissions)
    {
        $rights = json_decode($permissions, true);
        $rights = self::convertDataQualityResolution($rights);
        $rights = self::convertDoubleData($rights);
        foreach ( $rights as $key => $value ) {
            if ( $value === 'on' ) {
                $rights[$key] = 1;
            }
        }

        return json_encode($rights);
    }

    public static function getDisplayTextForRights(bool $allRights = false, bool $extraRights = false)
    {
        global $lang;
        $rights = [
            'design'                         => $lang['rights_135'],
            'user_rights'                    => $lang['app_05'],
            'data_access_groups'             => $lang['global_22'],
            'dataViewing'                    => $lang['rights_373'],
            'dataExport'                     => $lang['rights_428'],
            'alerts'                         => $lang['global_154'],
            'reports'                        => $lang['rights_96'],
            'graphical'                      => $lang['report_builder_78'],
            'participants'                   => $lang['app_24'],
            'calendar'                       => $lang['app_08'] . ' ' . $lang['rights_357'],
            'data_import_tool'               => $lang['app_01'],
            'data_comparison_tool'           => $lang['app_02'],
            'data_logging'                   => $lang['app_07'],
            'file_repository'                => $lang['app_04'],
            'double_data'                    => $lang['rights_50'],
            'lock_record_customize'          => $lang['app_11'],
            'lock_record'                    => $lang['rights_97'],
            'randomization'                  => $lang['app_21'],
            'data_quality_design'            => $lang['dataqueries_38'],
            'data_quality_execute'           => $lang['dataqueries_39'],
            'data_quality_resolution'        => $lang['dataqueries_137'],
            'api'                            => $lang['setup_77'],
            'mobile_app'                     => $lang['global_118'],
            'realtime_webservice_mapping'    => $lang['ws_292'] . '/' . $lang['ws_30'] . ' ' . $lang['ws_19'],
            'realtime_webservice_adjudicate' => $lang['ws_292'] . '/' . $lang['ws_30'] . ' ' . $lang['ws_20'],
            'dts'                            => $lang['rights_132'],
            'mycap_participants'             => $lang['rights_437'],
            'record_create'                  => $lang['rights_99'],
            'record_rename'                  => $lang['rights_100'],
            'record_delete'                  => $lang['rights_101']

        ];
        if ( $allRights ) {
            $rights['random_setup']                    = $lang['app_21'] . ' - ' . $lang['rights_142'];
            $rights['random_dashboard']                = $lang['app_21'] . ' - ' . $lang['rights_143'];
            $rights['random_perform']                  = $lang['app_21'] . ' - ' . $lang['rights_144'];
            $rights['data_quality_resolution_view']    = $lang['dataqueries_140'];
            $rights['data_quality_resolution_open']    = $lang['dataqueries_137'] . ' - ' . $lang['messaging_175'];
            $rights['data_quality_resolution_respond'] = $lang['dataqueries_137'] . ' - ' . $lang['dataqueries_152'];
            $rights['data_quality_resolution_close']   = $lang['dataqueries_137'] . ' - ' . $lang['bottom_90'];
            $rights['double_data_reviewer']            = $lang['dashboard_44'] . ' ' . $lang['rights_51'];
            $rights['double_data_person']              = $lang['dashboard_44'] . ' ' . $lang['rights_52'];
            $rights['api_export']                      = $lang['rights_139'];
            $rights['api_import']                      = $lang['rights_314'];
            $rights['mobile_app_download_data']        = $lang['rights_306'];
            $rights['lock_record_multiform']           = $lang['rights_370'];
        }
        // These rights won't be displayed in the SAG table - useful for getting display text for a single right
        if ( $extraRights ) {
            $rights['lock_record_esignature'] = $lang['rights_116'];
            $rights['editSurveyResponses']    = $lang['rights_137'];
            $rights['viewAndEdit']            = $lang['rights_138'];
            $rights['fullAccess']             = $lang['rights_440'];
            $rights['readOnly']               = $lang['rights_61'];
            $rights['fullDataSet']            = $lang['rights_49'];
            $rights['removeIdentifiers']      = $lang['data_export_tool_290'];
            $rights['deidentified']           = $lang['rights_48'];
        }
        return $rights;
    }

    public static function getDisplayTextForRight(string $right, string $key = '')
    {
        $rights = self::getDisplayTextForRights(true, true);
        return $rights[$right] ?? $rights[$key] ?? $right;
    }

    public static function convertRightName($rightName)
    {

        $conversions = [
            'stats_and_charts'           => 'graphical',
            'manage_survey_participants' => 'participants',
            'logging'                    => 'data_logging',
            'data_quality_create'        => 'data_quality_design',
            'lock_records_all_forms'     => 'lock_record_multiform',
            'lock_records'               => 'lock_record',
            'lock_records_customization' => 'lock_record_customize'
        ];

        return $conversions[$rightName] ?? $rightName;
    }

    public function getAllRights()
    {
        $sql    = 'SHOW COLUMNS FROM redcap_user_rights';
        $result = $this->module->framework->query($sql, []);
        $rights = [];
        while ( $row = $result->fetch_assoc() ) {
            if ( !in_array($row['Field'], [ 'project_id', 'username', 'expiration', 'role_id', 'group_id', 'api_token', 'data_access_group' ], true) ) {
                $rights[$row['Field']] = $this->module->framework->escape($row['Field']);
            }
        }
        return $rights;
    }

    public function filterPermissions($rawArray)
    {
        $allRights                         = $this->getAllRights();
        $dataEntryString                   = self::convertDataEntryRightsArrayToString($rawArray);
        $dataExportString                  = self::convertExportRightsArrayToString($rawArray);
        $result                            = array_intersect_key($rawArray, $allRights);
        $result['data_export_instruments'] = $dataExportString;
        $result['data_entry']              = $dataEntryString;
        return $result;
    }

    public function getDefaultRights()
    {
        $allRights = $this->getAllRights();
        if ( isset($allRights['data_export_tool']) ) {
            $allRights['data_export_tool'] = 2;
        }
        if ( isset($allRights['data_import_tool']) ) {
            $allRights['data_import_tool'] = 0;
        }
        if ( isset($allRights['data_comparison_tool']) ) {
            $allRights['data_comparison_tool'] = 0;
        }
        if ( isset($allRights['data_logging']) ) {
            $allRights['data_logging'] = 0;
        }
        if ( isset($allRights['file_repository']) ) {
            $allRights['file_repository'] = 1;
        }
        if ( isset($allRights['double_data']) ) {
            $allRights['double_data']          = 0;
            $allRights['double_data_reviewer'] = 1;
        }
        if ( isset($allRights['user_rights']) ) {
            $allRights['user_rights'] = 0;
        }
        if ( isset($allRights['lock_record']) ) {
            $allRights['lock_record'] = 0;
        }
        if ( isset($allRights['lock_record_multiform']) ) {
            $allRights['lock_record_multiform'] = 0;
        }
        if ( isset($allRights['lock_record_customize']) ) {
            $allRights['lock_record_customize'] = 0;
        }
        if ( isset($allRights['data_access_groups']) ) {
            $allRights['data_access_groups'] = 0;
        }
        if ( isset($allRights['graphical']) ) {
            $allRights['graphical'] = 1;
        }
        if ( isset($allRights['reports']) ) {
            $allRights['reports'] = 1;
        }
        if ( isset($allRights['design']) ) {
            $allRights['design'] = 0;
        }
        if ( isset($allRights['alerts']) ) {
            $allRights['alerts'] = 0;
        }
        if ( isset($allRights['dts']) ) {
            $allRights['dts'] = 0;
        }
        if ( isset($allRights['calendar']) ) {
            $allRights['calendar'] = 1;
        }
        if ( isset($allRights['record_create']) ) {
            $allRights['record_create'] = 1;
        }
        if ( isset($allRights['record_rename']) ) {
            $allRights['record_rename'] = 0;
        }
        if ( isset($allRights['record_delete']) ) {
            $allRights['record_delete'] = 0;
        }
        if ( isset($allRights['participants']) ) {
            $allRights['participants'] = 1;
        }
        if ( isset($allRights['data_quality_design']) ) {
            $allRights['data_quality_design'] = 0;
        }
        if ( isset($allRights['data_quality_execute']) ) {
            $allRights['data_quality_execute'] = 0;
        }
        if ( isset($allRights['data_quality_resolution']) ) {
            $allRights['data_quality_resolution']      = 1;
            $allRights['data_quality_resolution_view'] = 1;
        }
        if ( isset($allRights['api_export']) ) {
            $allRights['api_export'] = 0;
        }
        if ( isset($allRights['api_import']) ) {
            $allRights['api_import'] = 0;
        }
        if ( isset($allRights['mobile_app']) ) {
            $allRights['mobile_app'] = 0;
        }
        if ( isset($allRights['mobile_app_download_data']) ) {
            $allRights['mobile_app_download_data'] = 0;
        }
        if ( isset($allRights['random_setup']) ) {
            $allRights['random_setup'] = 0;
        }
        if ( isset($allRights['random_dashboard']) ) {
            $allRights['random_dashboard'] = 0;
        }
        if ( isset($allRights['random_perform']) ) {
            $allRights['random_perform'] = 1;
        }
        if ( isset($allRights['realtime_webservice_mapping']) ) {
            $allRights['realtime_webservice_mapping'] = 0;
        }
        if ( isset($allRights['realtime_webservice_adjudicate']) ) {
            $allRights['realtime_webservice_adjudicate'] = 0;
        }
        if ( isset($allRights['mycap_participants']) ) {
            $allRights['mycap_participants'] = 1;
        }
        return $allRights;
    }

}