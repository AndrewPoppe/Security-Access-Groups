<?php

namespace YaleREDCap\SecurityAccessGroups;

class SAGEditForm
{
    private $module;
    private $rights;
    private $newSag;
    private $sagName;
    private $sagId;
    private $lang;
    private $allRights;
    private $contextMessage;
    public function __construct(
        SecurityAccessGroups $module, array $rights, bool $newSag,
        $sagName = '',
        $sagId = ''
    ) {
        global $lang;
        $this->module    = $module;
        $this->rights    = $rights;
        $this->newSag    = $newSag;
        $this->sagName   = $sagName;
        $this->sagId     = $sagId;
        $this->lang      = $lang;
        $rightsUtilities = new RightsUtilities($module);
        $this->allRights = $rightsUtilities->getAllRights();

        $newMessage           = 'Creating new Security Access Group';
        $existingMessage      = 'Editing existing Security Access Group';
        $messageSuffix        = ' "<strong>' . \REDCap::escapeHtml($sagName) . '</strong>"';
        $this->contextMessage = ($newSag ? $newMessage : $existingMessage) . $messageSuffix;
        $this->module->framework->log('thing2', [
            'sag_id'      => $this->sagId,
            'sag_name'    => $this->sagName,
            'rights'      => json_encode($this->rights),
            'dataViewing' => $this->rights['dataViewing'],
        ]);
    }

    public function getForm()
    {
        $formContents = '';
        $formContents .= $this->getFormStart();
        $formContents .= $this->getSagNameField();
        $formContents .= $this->getHighLevelPrivileges();
        $formContents .= $this->getProjectSetupDesign();
        $formContents .= $this->getUserRights();
        $formContents .= $this->getDataAccessGroups();
        $formContents .= $this->getOtherPrivileges();
        $formContents .= $this->getMycapMobileApp();
        $formContents .= $this->getSurveyDistTool();
        $formContents .= $this->getAlerts();
        $formContents .= $this->getCalendar();
        $formContents .= $this->getReports();
        $formContents .= $this->getStatsAndCharts();
        $formContents .= $this->getDoubleDataEntry();
        $formContents .= $this->getDataImportTool();
        $formContents .= $this->getDataComparisonTool();
        $formContents .= $this->getLogging();
        $formContents .= $this->getFileRepository();
        $formContents .= $this->getRandomization();
        $formContents .= $this->getDataQuality();
        $formContents .= $this->getDataQualityResolution();
        $formContents .= $this->getAPI();
        $formContents .= $this->getDDPorCDIS();
        $formContents .= $this->getDTS();
        $formContents .= $this->getMobileApp();
        $formContents .= $this->getRecordRights();
        $formContents .= $this->getLockRecords();
        $formContents .= $this->getFormMiddle();
        $formContents .= $this->getDataViewing();
        $formContents .= $this->getDataExport();
        $formContents .= $this->getFormEnd();
        return $formContents;
    }

    private function getFormStart()
    {
        $alertClass = $this->newSag ? "alert-success" : "alert-primary";
        $label      = $this->lang['rights_431'];
        return <<<"EOT"
        <div class="modal-xl modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #e9e9e9; padding-top: 0.5rem; padding-bottom: 0.5rem;">
                    <span class="modal-title" id="staticBackdropLabel" style="font-size: 1rem;">
                        <i class="fa-solid fa-fw fa-user-tag"></i> $this->contextMessage
                    </span>
                    <button type="button" class="btn-close align-self-center" data-bs-dismiss="modal" data-dismiss="modal"
                    aria-label="Close"></button>
                </div>
                <div class="modal-body">
                <div style="text-align:center; margin: 15px 0;" class="fs14 alert $alertClass">
                    <i class="fa-solid fa-fw fa-user-tag"></i> $this->contextMessage
                </div>
                <form id="SAG_Setting">
                    <div class="hidden">
                        <input name="newSag" value="$this->newSag">
                    </div>
                    <div class="row">
                        <div class="col" style='width:475px;'>
                            <div class='card' style='border-color:#00000060;'>
                                <div class='card-header font-weight-bold fs14' style='background-color:#00000017;'>
                                    $label
                                </div>
                            <div class='card-body p-3' style='background-color:#00000007;'>
        EOT;
    }

    private function getSagNameField()
    {
        $label   = 'SAG name:';
        $sagName = $this->module->escape($this->sagName);
        $hidden  = $this->newSag ? "hidden" : '';
        return <<<"EOT"
        <!-- EDIT SAG NAME -->
        <div class="SUR-form-row row $hidden">
            <div class="col" colspan='2'>
                <i class="fa-solid fa-fw fa-id-card"></i>&nbsp;&nbsp;$label
                <input type='text' value="$sagName"
                    class='x-form-text x-form-field' name='sag_name_edit'>
            </div>
        </div>
        EOT;
    }

    private function getHighLevelPrivileges()
    {
        $label = $this->lang['rights_299'];
        return <<<"EOT"
        <!-- HIGHEST LEVEL PRIVILEGES -->
        <hr>
        <div class="SUR-form-row row">
            <div class="col section-header" colspan='2'>
                $label
            </div>
        </div>
        EOT;
    }

    private function getProjectSetupDesign()
    {
        if ( isset($this->allRights['design']) ) {
            $label   = $this->lang['rights_135'];
            $checked = $this->rights['design'] == 1 ? 'checked' : '';
            return <<<"EOT"
            <!-- Project Setup/Design -->
            <div class="SUR-form-row row">
                <div class="col">
                    <i class="fa-solid fa-fw fa-tasks"></i>&nbsp;&nbsp;$label
                </div>
                <div class="col">
                    <input type='checkbox' $checked
                        name='design'>
                </div>
            </div>
            EOT;
        }
    }
    private function getUserRights()
    {
        if ( isset($this->allRights['user_rights']) ) {
            $label   = $this->lang['app_05'];
            $checked = $this->rights['user_rights'] == 1 ? 'checked' : '';
            return <<<"EOT"
            <!-- User Rights -->
            <div class="SUR-form-row row">
                <div class="col">
                    <i class="fa-solid fa-fw fa-user"></i>&nbsp;&nbsp;$label
                </div>
                <div class="col">
                    <input type='checkbox' $checked
                        name='user_rights'>
                </div>
            </div>
            EOT;
        }
    }
    private function getDataAccessGroups()
    {
        if ( isset($this->allRights['data_access_groups']) ) {
            $label   = $this->lang['global_22'];
            $checked = $this->rights['data_access_groups'] == 1 ? 'checked' : '';
            return <<<"EOT"
            <!--Data Access Groups -->
            <div class="SUR-form-row row">
                <div class="col">
                    <i class="fa-solid fa-fw fa-users"></i>&nbsp;&nbsp;$label
                </div>
                <div class="col">
                    <input type='checkbox' $checked name='data_access_groups'>
                </div>
            </div>
            EOT;
        }
    }
    private function getOtherPrivileges()
    {
        $label = $this->lang['rights_300'];
        return <<<"EOT"
        <!-- OTHER PRIVILEGES -->
        <hr>
        <div class="SUR-form-row row">
            <div class="col section-header" colspan='2'>
                $label
            </div>
        </div>
        EOT;
    }
    private function getMycapMobileApp()
    {
        if ( isset($this->allRights['mycap_participants']) ) {
            $label   = $this->lang['rights_437'];
            $checked = $this->rights['mycap_participants'] == 1 ? 'checked' : '';
            $imgPath = APP_PATH_IMAGES . "mycap_logo_black.png";
            return <<<"EOT"
            <!-- MyCap Mobile App -->
            <div class="SUR-form-row row">
                <div class="col">
                    <img style='height:1rem;' alt='mycap_logo'
                        src='$imgPath'>&nbsp;$label
                </div>
                <div class="col">
                    <input type='checkbox' $checked name='mycap_participants'>
                </div>
            </div>
            EOT;
        }
    }
    private function getSurveyDistTool()
    {
        if ( isset($this->allRights['participants']) ) {
            $label   = $this->lang['app_24'];
            $checked = $this->rights['participants'] == 1 ? 'checked' : '';
            return <<<"EOT"
            <!-- Survey Distribution Tool -->
            <div class="SUR-form-row row">
                <div class="col">
                    <div>
                        <i
                            class="fa-solid fa-fw fa-chalkboard-teacher"></i>&nbsp;&nbsp;$label
                    </div>
                </div>
                <div class="col">
                    <input type='checkbox' $checked name='participants'>
                </div>
            </div>
            EOT;
        }
    }
    private function getAlerts()
    {
        if ( isset($this->allRights['alerts']) ) {
            $label   = $this->lang['global_154'];
            $checked = $this->rights['alerts'] == 1 ? 'checked' : '';
            return <<<"EOT"
            <!-- Alerts -->
            <div class="SUR-form-row row">
                <div class="col">
                    <i class="fa-solid fa-fw fa-bell"></i>&nbsp;&nbsp;$label
                </div>
                <div class="col">
                    <input type='checkbox' $checked
                        name='alerts'>
                </div>
            </div>
            EOT;
        }
    }
    private function getCalendar()
    {
        if ( isset($this->allRights['calendar']) ) {
            $label1  = $this->lang['app_08'];
            $label2  = $this->lang['rights_357'];
            $checked = $this->rights['calendar'] == 1 ? 'checked' : '';
            return <<<"EOT"
            <!-- Calendar -->
            <div class="SUR-form-row row">
                <div class="col">
                    <i class="far fa-calendar-alt"></i>&nbsp;&nbsp;
                    $label1 $label2
                </div>
                <div class="col">
                    <input type='checkbox' $checked name='calendar'>
                </div>
            </div>
            EOT;
        }
    }
    private function getReports()
    {
        if ( isset($this->allRights['reports']) ) {
            $label1  = $this->lang['rights_356'];
            $label2  = $this->lang['report_builder_130'];
            $checked = $this->rights['reports'] == 1 ? 'checked' : '';
            return <<<"EOT"
            <!-- Reports -->
            <div class="SUR-form-row row">
                <div class="col">
                    <i class="fa-solid fa-fw fa-search"></i>&nbsp;&nbsp;$label1
                    <div class="extra-text">$label2</div>
                </div>
                <div class="col">
                    <input type='checkbox' $checked name='reports'>
                </div>
            </div>
            EOT;
        }
    }
    private function getStatsAndCharts()
    {
        if ( isset($this->allRights['graphical']) ) {
            $label   = $this->lang['report_builder_78'];
            $checked = $this->rights['graphical'] == 1 ? 'checked' : '';
            return <<<"EOT"
            <!-- Graphical Data View & Stats -->
            <div class="SUR-form-row row">
                <div class="col">
                    <i
                        class="fa-solid fa-fw fa-chart-column"></i>&nbsp;&nbsp;$label
                </div>
                <div class="col">
                    <input type='checkbox' $checked name='graphical'>
                </div>
            </div>
            EOT;
        }
    }
    private function getDoubleDataEntry()
    {
        if ( isset($this->allRights['double_data']) ) {
            $label1   = $this->lang['rights_50'];
            $label2   = $this->lang['rights_51'];
            $label3   = $this->lang['rights_52'] . ' #1';
            $label4   = $this->lang['rights_52'] . ' #2';
            $checked1 = $this->rights['double_data'] == 0 ? 'checked' : '';
            $checked2 = $this->rights['double_data'] == 1 ? 'checked' : '';
            $checked3 = $this->rights['double_data'] == 2 ? 'checked' : '';
            return <<<"EOT"
            <div class="SUR-form-row row">
                <div class="col mt-1">
                    <i class="fa-solid fa-fw fa-users"></i>&nbsp;&nbsp;$label1
                </div>
                <div class="col">
                    <div class="form-check">
                        <input id='double_data_reviewer' class="form-check-input" type='radio'
                            name='double_data' $checked1 value='0'>
                        <label for="double_data_reviewer" class="form-check-label">$label2</label>
                    </div>
                    <div class="form-check">
                        <input id='double_data_p1' class="form-check-input" type='radio'
                            name='double_data' $checked2 value='1'>
                        <label for="double_data_p1" class="form-check-label">$label3</label>
                    </div>
                    <div class="form-check">
                        <input id='double_data_p2' class="form-check-input" type='radio'
                            name='double_data' $checked3 value='2'>
                        <label for="double_data_p2" class="form-check-label">$label4</label>
                    </div>
                </div>
            </div>
            EOT;
        }
    }
    private function getDataImportTool()
    {
        if ( isset($this->allRights['data_import_tool']) ) {
            $label   = $this->lang['app_01'];
            $checked = $this->rights['data_import_tool'] == 1 ? 'checked' : '';
            return <<<"EOT"
            <div class="SUR-form-row row">
                <div class="col">
                    <i class="fa-solid fa-fw fa-file-import"></i>&nbsp;&nbsp;$label
                </div>
                <div class="col">
                    <input type='checkbox' $checked name='data_import_tool'>
                </div>
            </div>
            EOT;
        }
    }
    private function getDataComparisonTool()
    {
        if ( isset($this->allRights['data_comparison_tool']) ) {
            $label   = $this->lang['app_02'];
            $checked = $this->rights['data_comparison_tool'] == 1 ? 'checked' : '';
            return <<<"EOT"
            <div class="SUR-form-row row">
                <div class="col">
                    <i class="fa-solid fa-fw fa-not-equal"></i>&nbsp;&nbsp;$label
                </div>
                <div class="col">
                    <input type='checkbox' $checked name='data_comparison_tool'>
                </div>
            </div>
            EOT;
        }
    }
    private function getLogging()
    {
        if ( isset($this->allRights['data_logging']) ) {
            $label   = $this->lang['app_07'];
            $checked = $this->rights['data_logging'] == 1 ? 'checked' : '';
            return <<<"EOT"
            <div class="SUR-form-row row">
                <div class="col">
                    <i class="fa-solid fa-fw fa-receipt"></i>&nbsp;&nbsp;$label
                </div>
                <div class="col">
                    <input type='checkbox' $checked name='data_logging'>
                </div>
            </div>
            EOT;
        }
    }
    private function getFileRepository()
    {
        if ( isset($this->allRights['file_repository']) ) {
            $label   = $this->lang['app_04'];
            $checked = $this->rights['file_repository'] == 1 ? 'checked' : '';
            return <<<"EOT"
            <div class="SUR-form-row row">
                <div class="col">
                    <i class="fa-solid fa-fw fa-folder-open"></i>&nbsp;&nbsp;$label
                </div>
                <div class="col">
                    <input type='checkbox' $checked name='file_repository'>
                </div>
            </div>
            EOT;
        }
    }
    private function getRandomization()
    {
        if ( isset($this->allRights['random_setup']) ) {
            $label1   = $this->lang['app_21'];
            $label2   = $this->lang['rights_142'];
            $label3   = $this->lang['rights_143'];
            $label4   = $this->lang['rights_144'];
            $checked1 = $this->rights['random_setup'] == 1 ? 'checked' : '';
            $checked2 = $this->rights['random_dashboard'] == 1 ? 'checked' : '';
            $checked3 = $this->rights['random_perform'] == 1 ? 'checked' : '';
            return <<<"EOT"
            <div class="SUR-form-row row">
                <div class="col mt-1">
                    <i class="fa-solid fa-fw fa-random"></i>&nbsp;&nbsp;$label1
                </div>
                <div class="col">
                    <div class="form-check">
                        <input class='form-check-input' type='checkbox' id='random_setup'
                            $checked1 name='random_setup'>
                        <label class='form-check-label'
                            for='random_setup'>$label2</label>
                    </div>
                    <div class="form-check">
                        <input class='form-check-input' type='checkbox' id='random_dashboard'
                            $checked2 name='random_dashboard'>
                        <label class='form-check-label'
                            for='random_dashboard'>$label3</label>
                    </div>
                    <div class="form-check">
                        <input class='form-check-input' type='checkbox' id='random_perform'
                            $checked3 name='random_perform'>
                        <label class='form-check-label'
                            for='random_perform'>$label4</label>
                    </div>
                </div>
            </div>
            EOT;
        }
    }
    private function getDataQuality()
    {
        if ( isset($this->allRights['data_quality_design']) ) {
            $label1   = $this->lang['app_20'];
            $label2   = $this->lang['dataqueries_40'];
            $label3   = $this->lang['dataqueries_41'];
            $checked1 = $this->rights['data_quality_design'] == 1 ? 'checked' : '';
            $checked2 = $this->rights['data_quality_execute'] == 1 ? 'checked' : '';
            return <<<"EOT"
            <div class="SUR-form-row row">
                <div class="col mt-1">
                    <i
                        class="fa-solid fa-fw fa-clipboard-check"></i>&nbsp;&nbsp;$label1
                </div>
                <div class="col">
                    <div class="form-check">
                        <input class='form-check-input' type='checkbox' id='data_quality_design'
                            $checked1 name='data_quality_design'>
                        <label class='form-check-label'
                            for='data_quality_design'>$label2</label>
                    </div>
                    <div class="form-check">
                        <input class='form-check-input' type='checkbox' id='data_quality_execute'
                            $checked2 name='data_quality_execute'>
                        <label class='form-check-label'
                            for='data_quality_execute'>$label3</label>
                    </div>
                </div>
            </div>
            EOT;
        }
    }
    private function getDataQualityResolution()
    {
        if ( isset($this->allRights['data_quality_resolution']) ) {
            $label    = $this->lang['dataqueries_137'];
            $checked1 = $this->rights['data_quality_resolution_view'] == 1 ? 'checked' : '';
            $checked2 = $this->rights['data_quality_resolution_open'] == 1 ? 'checked' : '';
            $checked3 = $this->rights['data_quality_resolution_respond'] == 1 ? 'checked' : '';
            $checked4 = $this->rights['data_quality_resolution_close'] == 1 ? 'checked' : '';
            return <<<"EOT"
            <div class="SUR-form-row row">
                <div class="col mt-1">
                    <i
                        class='fa-solid fa-fw fa-comments'></i>&nbsp;&nbsp;$label
                </div>
                <div class="col">
                    <div class='form-check'>
                        <input
                            class='form-check-input data_quality_resolution data_quality_resolution_view'
                            type='checkbox' id='data_quality_resolution_view'
                            name='data_quality_resolution_view' $checked1
                            onchange="if(!this.checked) {\$('.data_quality_resolution').prop('checked', false);}">
                        <label class='form-check-label' for='data_quality_resolution_view'>View Queries</label>
                    </div>
                    <div class='form-check'>
                        <input
                            class='form-check-input data_quality_resolution data_quality_resolution_open'
                            type='checkbox' id='data_quality_resolution_open'
                            name='data_quality_resolution_open' $checked2
                            onchange="if(!this.checked) {\$('.data_quality_resolution_close').prop('checked', false);} else {\$('.data_quality_resolution_view').prop('checked', true);}">
                        <label class='form-check-label' for='data_quality_resolution_open'>Open Queries</label>
                    </div>
                    <div class='form-check'>
                        <input
                            class='form-check-input data_quality_resolution data_quality_resolution_respond'
                            type='checkbox' id='data_quality_resolution_respond'
                            name='data_quality_resolution_respond' $checked3
                            onchange="if(!this.checked) {\$('.data_quality_resolution_close').prop('checked', false);} else {\$('.data_quality_resolution_view').prop('checked', true);}">
                        <label class='form-check-label'
                            for='data_quality_resolution_respond'>Respond to Queries</label>
                    </div>
                    <div class='form-check'>
                        <input
                            class='form-check-input data_quality_resolution data_quality_resolution_close'
                            type='checkbox' id='data_quality_resolution_close'
                            name='data_quality_resolution_close' $checked4
                            onchange="if(this.checked) {\$('.data_quality_resolution').prop('checked', true);}">
                        <label class='form-check-label' for='data_quality_resolution_close'>Close Queries</label>
                    </div>
                </div>
            </div>
            EOT;
        }
    }
    private function getAPI()
    {
        if ( isset($this->allRights['api_export']) ) {
            $label1   = $this->lang['setup_77'];
            $label2   = $this->lang['rights_139'];
            $label3   = $this->lang['rights_314'];
            $checked1 = $this->rights['api_export'] == 1 ? 'checked' : '';
            $checked2 = $this->rights['api_import'] == 1 ? 'checked' : '';
            return <<<"EOT"
            <div class="SUR-form-row row">
                <div class="col mt-1">
                    <i
                        class="fa-solid fa-fw fa-laptop-code"></i>&nbsp;&nbsp;$label1
                </div>
                <div class="col">
                    <div class='form-check'>
                        <input class='form-check-input' id='api_export' $checked1 type='checkbox' name='api_export'>
                        <label class='form-check-label' for='api_export'>$label2</label>
                    </div>
                    <div class='form-check'>
                        <input class='form-check-input' id='api_import' name='api_import' $checked2 type='checkbox'>
                        <label class='form-check-label' for='api_import'>$label3</label>
                    </div>
                </div>
            </div>
            EOT;
        }
    }
    private function getDDPorCDIS()
    {
        if ( isset($this->allRights['realtime_webservice_mapping']) ) {
            $label1   = $this->lang['ws_19'];
            $label2   = $this->lang['ws_20'];
            $checked1 = $this->rights['realtime_webservice_mapping'] == 1 ? 'checked' : '';
            $checked2 = $this->rights['realtime_webservice_adjudicate'] == 1 ? 'checked' : '';
            return <<<"EOT"
            <div class="SUR-form-row row">
                <div class="col mt-1">
                    <div>
                        <i class="fa-solid fa-fw fa-database"></i>&nbsp;&nbsp; Clinical
                        Data Pull from EHR -or- Dynamic Data Pull from External Source
                        System
                    </div>
                </div>
                <div class="col">
                    <div class='form-check'>
                        <!-- Mapping rights -->
                        <input class='form-check-input' type="checkbox"
                            id="realtime_webservice_mapping" $checked1 name="realtime_webservice_mapping">
                        <label class='form-check-label'
                            for='realtime_webservice_mapping'>$label1</label>
                    </div>
                    <div class='form-check'>
                        <!-- Adjudication rights -->
                        <input class='form-check-input' type="checkbox"
                            id="realtime_webservice_adjudicate" $checked2 name="realtime_webservice_adjudicate">
                        <label class='form-check-label'
                            for='realtime_webservice_adjudicate'>$label2</label>
                    </div>
                </div>
            </div>
            EOT;
        } else {
            $val1 = $this->rights['realtime_webservice_mapping'];
            $val2 = $this->rights['realtime_webservice_adjudicate'];
            return <<<"EOT"
            <!-- Hide input fields to maintain values if setting is disabled at project level -->
            <input type="hidden" name="realtime_webservice_mapping" value="$val1">
            <input type="hidden" name="realtime_webservice_adjudicate" value="$val2">
            EOT;
        }
    }

    private function getDTS()
    {
        if ( isset($this->allRights['dts']) ) {
            $label   = $this->lang['rights_132'];
            $checked = $this->rights['dts'] == 1 ? 'checked' : '';
            return <<<"EOT"
        <div class="SUR-form-row row">
        <div class="col" valign="top">
            <div>
                <i class="fa-solid fa-fw fa-database"></i>&nbsp;&nbsp;$label
            </div>
        </div>
        <div class="col" valign="top">

            <div>
                <input type="checkbox" $checked name="dts">
            </div>
        </div>
        </div>
        EOT;
        }
    }
    private function getMobileApp()
    {
        if ( isset($this->allRights['mobile_app']) ) {
            $label1   = $this->lang['rights_309'];
            $label2   = $this->lang['global_118'];
            $label3   = $this->lang['rights_307'];
            $label4   = $this->lang['rights_306'];
            $checked1 = $this->rights['mobile_app'] == 1 ? 'checked' : '';
            $checked2 = $this->rights['mobile_app_download_data'] == 1 ? 'checked' : '';
            return <<<"EOT"
            <hr>
            <div class="SUR-form-row row">
                <div class="col section-header" colspan='2'>$label1</div>
            </div>
            <div class="SUR-form-row row">
                <div class="col">
                    <i class="fa-solid fa-fw fa-tablet-alt"></i>&nbsp;&nbsp;$label2
                    <div class="extra-text">
                        $label3
                    </div>
                </div>
                <div class="col">
                    <input type='checkbox' $checked1 name='mobile_app'>

                </div>
            </div>
            <div class="SUR-form-row row">
                <div class="col">
                    $label4
                </div>
                <div class="col">
                    <div>
                        <input type='checkbox' $checked2 name='mobile_app_download_data'>
                    </div>
                </div>
            </div>
            EOT;
        }
    }

    private function getRecordRights()
    {
        $label1 = $this->lang['rights_119'];
        $result = <<<"EOT"
        <hr>
        <div class="SUR-form-row row">
            <div class="col section-header" colspan='2'>$label1</div>
        </div>
        EOT;
        if ( isset($this->allRights['record_create']) ) {
            $label   = $this->lang['rights_99'];
            $checked = $this->rights['record_create'] == 1 ? 'checked' : '';
            $result .= <<<"EOT"
            <div class="SUR-form-row row">
                <div class="col">
                    <i class="fa-solid fa-fw fa-plus-square"></i>&nbsp;&nbsp;$label
                </div>
                <div class="col">
                    <input type='checkbox' $checked name='record_create'>
                </div>
            </div>
            EOT;
        }
        if ( isset($this->allRights['record_rename']) ) {
            $label   = $this->lang['rights_100'];
            $checked = $this->rights['record_rename'] == 1 ? 'checked' : '';
            $result .= <<<"EOT"
            <div class="SUR-form-row row">
                <div class="col">
                    <i class="fa-solid fa-fw fa-exchange-alt"></i>&nbsp;&nbsp;$label
                </div>
                <div class="col">
                    <input type='checkbox' $checked name='record_rename'>
                </div>
            </div>
            EOT;
        }
        if ( isset($this->allRights['record_delete']) ) {
            $label   = $this->lang['rights_101'];
            $checked = $this->rights['record_delete'] == 1 ? 'checked' : '';
            $result .= <<<"EOT"
            <div class="SUR-form-row row">
                <div class="col">
                    <i class="fa-solid fa-fw fa-minus-square"></i>&nbsp;&nbsp;$label
                </div>
                <div class="col">
                    <input type='checkbox' $checked name='record_delete'>
                </div>
            </div>
            EOT;
        }
        return $result;
    }

    private function getLockRecords()
    {
        $label  = $this->lang['rights_130'];
        $result = <<<"EOT"
        <hr>
        <div class="SUR-form-row row">
            <div class="col section-header" colspan='2'>$label</div>
        </div>
        EOT;
        if ( isset($this->allRights['lock_record_customize']) ) {
            $label   = $this->lang['app_11'];
            $checked = $this->rights['lock_record_customize'] == 1 ? 'checked' : '';
            $result .= <<<"EOT"
            <div class="SUR-form-row row">
                <div class="col">
                    <div>
                        <i class="fa-solid fa-fw fa-lock"></i>&nbsp;&nbsp;$label
                    </div>
                </div>
                <div class="col">
                    <input type='checkbox' $checked name='lock_record_customize'>
                </div>
            </div>
            EOT;
        }
        if ( isset($this->allRights['lock_record']) ) {
            $label1   = $this->lang['rights_97'];
            $label2   = $this->lang['rights_371'];
            $label3   = $this->lang['rights_113'];
            $label4   = $this->lang['global_23'];
            $label5   = $this->lang['rights_115'];
            $label6   = $this->lang['rights_116'];
            $checked1 = $this->rights['lock_record'] == 0 ? 'checked' : '';
            $checked2 = $this->rights['lock_record'] == 1 ? 'checked' : '';
            $checked3 = $this->rights['lock_record'] == 2 ? 'checked' : '';
            $result .= <<<"EOT"
            <div class="SUR-form-row row">
                <div class="col mt-1">
                    <div>
                        <i class="fa-solid fa-fw fa-unlock-alt"></i>&nbsp;&nbsp;$label1 $label2
                    </div>
                    <div class="extra-text">$label3</div>
                </div>
                <div class="col">
                    <div class='form-check'>
                        <input class='form-check-input' type='radio' id='lock_record_0'
                            name='lock_record' $checked1 value='0'>
                        <label class='form-check-label'
                            for='lock_record_0'>$label4</label>
                    </div>
                    <div class='form-check'>
                        <input class='form-check-input' type='radio' id='lock_record_1'
                            name='lock_record' $checked2 value='1'>
                        <label class='form-check-label'
                            for='lock_record_1'>$label5</label>
                    </div>
                    <div class='form-check'>
                        <input class='form-check-input' type='radio' id='lock_record_2'
                            name='lock_record' $checked3 value='2'>
                        <label class='form-check-label'
                            for='lock_record_2'>$label6</label>
                    </div>
                </div>
            </div>
            EOT;
        }
        if ( isset($this->allRights['lock_record_multiform']) ) {
            $label   = $this->lang['rights_370'];
            $checked = $this->rights['lock_record_multiform'] == 1 ? 'checked' : '';
            $result .= <<<"EOT"
            <div class="SUR-form-row row">
                <div class="col">
                    <div>
                        <i class="fa-solid fa-fw fa-unlock-alt"></i>&nbsp;&nbsp;$label
                    </div>
                </div>
                <div class="col">
                    <div>
                        <input type='checkbox' $checked name='lock_record_multiform'>
                    </div>
                </div>
            </div>
            EOT;
        }
        return $result;
    }
    private function getFormMiddle()
    {
        $label1 = $this->lang['data_export_tool_291'];
        $label2 = $this->lang['rights_429'];
        return <<<"EOT"
                    </table>
                </div>
            </div>
        </div>
        <div class="col" style="padding-left:10px;">
            <div class='card' style='border-color:#00000060;'>
                <div class='card-header font-weight-bold fs14' style='background-color:#00000017;'>
                    $label1
                </div>
                <div class='card-body p-0' style='background-color:#00000007;'>
                    <div class="SUR-form-row row" style="margin: 10px 20px 10px 0;">
                        <div class="col extra-text" colspan='3'>
                            $label2
                        </div>
                    </div>
                    <div class="SUR-form-row row" style="margin: 20px;">
        EOT;
    }
    private function getDataViewing()
    {
        $label1   = $this->lang['rights_373'];
        $label2   = $this->lang['rights_47'];
        $label3   = $this->lang['rights_395'];
        $label4   = $this->lang['rights_61'];
        $label5   = $this->lang['rights_138'];
        $label6   = $this->lang['rights_137'];
        $checked1 = $this->rights['dataViewing'] == 0 ? 'checked' : '';
        $checked2 = $this->rights['dataViewing'] == 1 ? 'checked' : '';
        $checked3 = $this->rights['dataViewing'] == 2 ? 'checked' : '';
        $checked4 = $this->rights['dataViewing'] == 3 ? 'checked' : '';
        return <<<"EOT"
        <div class="col">
            <div class='fs13 pb-2 font-weight-bold'>$label1</div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="dataViewing"
                    id="dataViewingNoAccess" $checked1 value="0">
                <label class="form-check-label"
                    for="dataViewingNoAccess">$label2<br>$label3</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="dataViewing"
                    id="dataViewingReadOnly" $checked2 value="1">
                <label class="form-check-label"
                    for="dataViewingReadOnly">$label4</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="dataViewing"
                    id="dataViewingViewAndEdit" $checked3 value="2">
                <label class="form-check-label"
                    for="dataViewingViewAndEdit">$label5</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="dataViewing"
                    id="dataViewingViewAndEditSurveys" $checked4 value="3">
                <label class="form-check-label"
                    for="dataViewingViewAndEditSurveys">$label6</label>
            </div>
        </div>
        EOT;
    }
    private function getDataExport()
    {
        $label1   = $this->lang['rights_428'];
        $label2   = $this->lang['rights_47'];
        $label3   = $this->lang['rights_48'];
        $label4   = $this->lang['data_export_tool_290'];
        $label5   = $this->lang['rights_49'];
        $checked1 = $this->rights['dataExport'] == 0 ? 'checked' : '';
        $checked2 = $this->rights['dataExport'] == 1 ? 'checked' : '';
        $checked3 = $this->rights['dataExport'] == 2 ? 'checked' : '';
        $checked4 = $this->rights['dataExport'] == 3 ? 'checked' : '';
        return <<<"EOT"
        <div class="col" style='color:#B00000;'>
            <div class='fs13 pb-2 font-weight-bold'>$label1</div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="dataExport"
                    id="dataExportNoAccess" $checked1 value="0">
                <label class="form-check-label"
                    for="dataExportNoAccess">$label2</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="dataExport"
                    id="dataExportDeidentified" $checked2 value="1">
                <label class="form-check-label"
                    for="dataExportDeidentified">$label3</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="dataExport"
                    id="dataExportIdentifiers" $checked3 value="2">
                <label class="form-check-label"
                    for="dataExportIdentifiers">$label4</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="dataExport"
                    id="dataExportFullDataset" $checked4 value="3">
                <label class="form-check-label"
                    for="dataExportFullDataset">$label5</label>
            </div>
        </div>
        EOT;
    }
    private function getFormEnd()
    {
        $buttonClass = $this->newSag ? 'btn-success' : 'btn-primary';
        $label       = $this->newSag ? 'Save New SAG' : 'Save Changes';
        $result      = <<<"EOT"
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button id="SAG_Save" type="button"
                class="btn $buttonClass">$label</button>
            <button id="SUR_Cancel" type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                data-dismiss="modal">Cancel
            </button>
        EOT;
        if ( !$this->newSag ) {
            $result .= <<<"EOT"
            <button id="SAG_Copy" type="button" class="btn btn-info btn-sm">Copy SAG</button>
            <button id="SAG_Delete" type="button" class="btn btn-danger btn-sm">Delete SAG</button>
            EOT;
        }
        $result .= <<<"EOT"
                </div>
            </div>
        </div>
        EOT;
        return $result;
    }
}