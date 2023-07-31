<?php

namespace YaleREDCap\SecurityAccessGroups;

class RightsChecker
{
    private $module;
    private $rightsToCheck;
    private $acceptableRights;

    private $badRights = [];
    private $dataViewing;
    private $dataExport;
    private $accountedFor = false;
    private $projectId;
    private SAGProject $project;

    private bool $checkAllRights;
    public function __construct(SecurityAccessGroups $module, array $rightsToCheck, array $acceptableRights, $projectId, $checkAllRights = false)
    {
        $this->module           = $module;
        $this->rightsToCheck    = $rightsToCheck;
        $this->acceptableRights = $acceptableRights;
        $this->dataViewing      = intval($acceptableRights["dataViewing"]);
        $this->dataExport       = intval($acceptableRights["dataExport"]);
        $this->projectId        = $projectId;
        $this->project          = new SAGProject($this->module, $this->projectId);
        $this->checkAllRights   = $checkAllRights;
    }

    private function isSafeRight($rightName)
    {
        $safeRights = [
            "project_id",
            "username",
            "role_id",
            "user",
            "submit-action",
            "role_name",
            "role_name_edit",
            "redcap_csrf_token",
            "expiration",
            "group_role",
            "group_id",
            "api_token",
            "data_access_group_id",
            "unique_role_name",
            "role_label",
            "notify_email"
        ];
        return in_array($rightName, $safeRights, true);
    }

    private function checkRight($right)
    {
        if ( $this->acceptableRights[$right] == 0 ) {
            $this->badRights[] = RightsUtilities::getDisplayTextForRight($right);
        }
    }

    private function checkSurveyEditRights($right, $value)
    {
        $isSurveyResponseEditingRight = substr_compare($right, "form-editresp-", 0, strlen("form-editresp-")) === 0;
        if ( !$isSurveyResponseEditingRight ) {
            return;
        }
        $this->accountedFor = true;
        if ( $value == "on" && $this->dataViewing < 3 ) {
            $this->badRights[] = "Data Viewing - Edit Survey Responses";
        }
    }

    private function checkDataViewingRights($right, $value)
    {
        $isDataViewingRight = substr_compare($right, "form-", 0, strlen("form-")) === 0;
        if ( !$isDataViewingRight ) {
            return;
        }
        $this->accountedFor = true;
        // 0: no access, 2: read only, 1: view and edit, 3: edit survey responses
        if ( $value === '3' && $this->dataViewing < 3 ) {
            $this->badRights[] = "Data Viewing - Edit Survey Responses";
        } elseif ( $value === '1' && $this->dataViewing < 2 ) {
            $this->badRights[] = "Data Viewing - View & Edit";
        } elseif ( $value === '2' && $this->dataViewing < 1 ) {
            $this->badRights[] = "Data Viewing - Read Only";
        }
    }

    private function checkDataViewingRights2($right)
    {
        $isDataViewingRight = substr_compare($right, "data_entry", 0, strlen("data_entry")) === 0;
        if ( !$isDataViewingRight ) {
            return;
        }
        $this->accountedFor = true;
        if ( $right === "data_entry3" && $this->dataViewing < 3 ) {
            $this->badRights[] = "Data Viewing - Edit Survey Responses";
        } elseif ( $right === "data_entry1" && $this->dataViewing < 2 ) {
            $this->badRights[] = "Data Viewing - View & Edit";
        } elseif ( $right === "data_entry2" && $this->dataViewing < 1 ) {
            $this->badRights[] = "Data Viewing - Read Only";
        }
    }

    private function checkDataExportRights($right, $value)
    {
        $isDataExportRight = substr_compare($right, "export-form-", 0, strlen("export-form-")) === 0;
        if ( !$isDataExportRight ) {
            return;
        }
        $this->accountedFor = true;
        // 0: no access, 2: deidentified, 3: remove identifiers, 1: full data set
        if ( $value === '1' && $this->dataExport < 3 ) {
            $this->badRights[] = "Data Export - Full Data Set";
        } elseif ( $value === '3' && $this->dataExport < 2 ) {
            $this->badRights[] = "Data Export - Remove Identifiers";
        } elseif ( $value === '2' && $this->dataExport < 1 ) {
            $this->badRights[] = "Data Export - De-Identified";
        }
    }

    private function checkDataExportRights2($right)
    {
        $isDataExportRight = substr_compare($right, "data_export", 0, strlen("data_export")) === 0;
        if ( !$isDataExportRight ) {
            return;
        }
        $this->accountedFor = true;
        // 0: no access, 2: deidentified, 3: remove identifiers, 1: full data set
        if ( $right === "data_export1" && $this->dataExport < 3 ) {
            $this->badRights[] = "Data Export - Full Data Set";
        } elseif ( $right === "data_export3" && $this->dataExport < 2 ) {
            $this->badRights[] = "Data Export - Remove Identifiers";
        } elseif ( $right === "data_export2" && $this->dataExport < 1 ) {
            $this->badRights[] = "Data Export - De-Identified";
        }
    }

    private function checkRecordLockingRights($right, $value)
    {
        $isRecordLockRight = $right == "lock_record";
        if ( !$isRecordLockRight ) {
            return;
        }
        $this->accountedFor = true;
        if ( intval($value) > intval($this->acceptableRights[$right]) ) {
            $this->badRights[] = "Record Locking" . ($value == 2 ? " with E-signature" : "");
        }
    }

    private function checkDoubleDataRights($right, $value)
    {
        $isDoubleDataRight = $right == "double_data";
        if ( !$isDoubleDataRight ) {
            return;
        }

        $this->accountedFor = true;

        if ( !$this->project->isDoubleDataEnabled() && !$this->checkAllRights ) {
            return;
        }
        // 0: reviewer
        // 1: double data person 1
        // 2: double data person 2
        $ddeReview = $this->acceptableRights["double_data_reviewer"] == 1;
        $ddePerson = $this->acceptableRights["double_data_person"] == 1;
        if ( intval($value) == 0 && !$ddeReview ) {
            $this->badRights[] = "Double Data Entry Reviewer";
        } elseif ( intval($value) > 0 && !$ddePerson ) {
            $this->badRights[] = "Double Data Entry Person";
        }
    }

    private function checkDataResolutionRights($right, $value)
    {
        $isDataResolutionRight = $right == "data_quality_resolution";
        if ( !$isDataResolutionRight ) {
            return;
        }
        $this->accountedFor = true;

        if ( !$this->project->isDataResolutionWorkflowEnabled() && !$this->checkAllRights ) {
            return;
        }

        // 0: no access
        // 1: view only
        // 4: open queries only
        // 2: respond only to opened queries
        // 5: open and respond to queries
        // 3: open, close, and respond to queries
        $dqrView    = $this->acceptableRights['data_quality_resolution_view'] == 1;
        $dqrOpen    = $this->acceptableRights['data_quality_resolution_open'] == 1;
        $dqrRespond = $this->acceptableRights['data_quality_resolution_respond'] == 1;
        $dqrClose   = $this->acceptableRights['data_quality_resolution_close'] == 1;

        if ( $value != '4' && !$dqrView ) {
            $badRight          = RightsUtilities::getDisplayTextForRight('data_quality_resolution_view');
            $this->badRights[] = $badRight;
        }
        if ( ($value == '4' || $value == '5' || $value == '3') && !$dqrOpen ) {
            $badRight          = RightsUtilities::getDisplayTextForRight('data_quality_resolution_open');
            $this->badRights[] = $badRight;
        }
        if ( ($value == '2' || $value == '5' || $value == '3') && !$dqrRespond ) {
            $badRight          = RightsUtilities::getDisplayTextForRight('data_quality_resolution_respond');
            $this->badRights[] = $badRight;
        }
        if ( $value == '3' && !$dqrClose ) {
            $badRight          = RightsUtilities::getDisplayTextForRight('data_quality_resolution_close');
            $this->badRights[] = $badRight;
        }
    }

    private function checkSurveyRights($right)
    {
        if ( $right !== 'participants' ) {
            return;
        }
        $this->accountedFor = true;

        if ( !$this->project->areSurveysEnabled() && !$this->checkAllRights ) {
            return;
        }

        $this->checkRight($right);
    }

    private function checkMyCapRights($right)
    {
        if ( $right !== 'mycap_participants' ) {
            return;
        }
        $this->accountedFor = true;


        if ( !$this->project->isMyCapEnabled() && !$this->checkAllRights ) {
            return;
        }

        $this->checkRight($right);
    }

    private function checkStatsAndCharts($right)
    {
        if ( $right !== 'graphical' ) {
            return;
        }
        $this->accountedFor = true;

        if ( !$this->project->isStatsAndChartsEnabled() && !$this->checkAllRights ) {
            return;
        }

        $this->checkRight($right);
    }

    private function checkRandomizationRights($right)
    {
        $isRandomizationRight = in_array($right, [ 'random_setup', 'random_dashboard', 'random_perform' ], true);
        if ( !$isRandomizationRight ) {
            return;
        }
        $this->accountedFor = true;

        if ( !$this->project->isRandomizationEnabled() && !$this->checkAllRights ) {
            return;
        }

        $this->checkRight($right);

    }

    private function checkCDPandDDP($right)
    {
        $isCDPorDDPRight = in_array($right, [ 'realtime_webservice_mapping', 'realtime_webservice_adjudicate' ], true);
        if ( !$isCDPorDDPRight ) {
            return;
        }
        $this->accountedFor = true;

        if ( !$this->project->isCDPorDDPEnabled() && !$this->checkAllRights ) {
            return;
        }

        $this->checkRight($right);
    }

    public function checkRights()
    {
        foreach ( $this->rightsToCheck as $right => $value ) {

            $this->accountedFor = false;

            // Do this first because a 0 is meaningful here
            $this->checkDoubleDataRights($right, $value);

            // Otherwise we don't care about 0's
            if ( $value === 0 || $value === '0' ) {
                continue;
            }

            $right = RightsUtilities::convertRightName($right);
            if ( $this->isSafeRight($right) ) {
                continue;
            }

            $this->checkCDPandDDP($right);
            $this->checkMyCapRights($right);
            $this->checkRandomizationRights($right);
            $this->checkStatsAndCharts($right);
            $this->checkSurveyRights($right);

            $this->checkSurveyEditRights($right, $value);
            $this->checkDataViewingRights($right, $value);
            $this->checkDataExportRights($right, $value);
            $this->checkRecordLockingRights($right, $value);
            $this->checkDataResolutionRights($right, $value);

            if ( !$this->accountedFor && $this->acceptableRights[$right] == 0 ) {
                $this->badRights[] = RightsUtilities::getDisplayTextForRight($right);
            }
        }
        return array_values(array_unique($this->badRights, SORT_REGULAR));
    }

    public function checkRights2()
    {
        foreach ( $this->rightsToCheck as $right => $value ) {

            $this->accountedFor = false;

            // Do this first because a 0 is meaningful here
            $this->checkDoubleDataRights($right, $value);

            // Otherwise we don't care about 0's
            if ( $value === 0 || $value === '0' ) {
                continue;
            }

            $right = RightsUtilities::convertRightName($right);
            if ( $this->isSafeRight($right) ) {
                continue;
            }

            $this->checkCDPandDDP($right);
            $this->checkMyCapRights($right);
            $this->checkRandomizationRights($right);
            $this->checkStatsAndCharts($right);
            $this->checkSurveyRights($right);

            $this->checkDataViewingRights2($right);
            $this->checkDataExportRights2($right);
            $this->checkRecordLockingRights($right, $value);
            $this->checkDataResolutionRights($right, $value);


            if ( !$this->accountedFor && $this->acceptableRights[$right] == 0 ) {
                $this->badRights[] = RightsUtilities::getDisplayTextForRight($right);
            }
        }
        return array_values(array_unique($this->badRights, SORT_REGULAR));
    }
}