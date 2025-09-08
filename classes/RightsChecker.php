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
    /**
     * @param SecurityAccessGroups $module
     * @param array $options  Options include:
     *                        - rightsToCheck (array) - The rights to check
     *                        - acceptableRights (array) - The acceptable rights for the user
     *                        - projectId (string|null) - The project ID (if not provided, will be taken from $project)
     *                        - checkAllRights (bool) - Whether to check all rights, even if the feature is disabled (default: false)
     */
    public function __construct(SecurityAccessGroups $module, array $options)
    {
        $this->module           = $module;
        $this->rightsToCheck    = $options['rightsToCheck'] ?? [];
        $this->acceptableRights = $options['acceptableRights'] ?? [];
        $this->dataViewing      = (int) ($this->acceptableRights['dataViewing'] ?? 0);
        $this->dataExport       = (int) ($this->acceptableRights['dataExport'] ?? 0);
        if ( $options['project'] ) {
            $this->project   = $options['project'];
            $this->projectId = $this->project->projectId;
        } else {
            $this->projectId = $options['projectId'] ?? null;
            $this->project   = new SAGProject($this->module, [ 
                'projectId' => $this->projectId, 
                'getConfig' => true 
            ]);

        }
        $this->checkAllRights = $options['checkAllRights'] ?? false;
    }

    private function isSafeRight($rightName)
    {
        $safeRights = [
            'project_id',
            'username',
            'role_id',
            'user',
            'submit-action',
            'role_name',
            'role_name_edit',
            'redcap_csrf_token',
            'expiration',
            'group_role',
            'group_id',
            'api_token',
            'data_access_group',
            'data_access_group_id',
            'unique_role_name',
            'role_label',
            'notify_email',
        ];

        // There was no separate email logging right prior to REDCap 14.4.0
        $redcapVersion      = defined('REDCAP_VERSION') ? REDCAP_VERSION : '99.99.99';
        if (\REDCap::versionCompare($redcapVersion, '14.4.0') < 0) {
            $safeRights[] = 'email_logging';
        }

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
        $isSurveyResponseEditingRight = substr_compare($right, 'form-editresp-', 0, strlen('form-editresp-')) === 0;
        if ( !$isSurveyResponseEditingRight ) {
            return;
        }
        $this->accountedFor = true;
        if ( $value == 'on' && $this->dataViewing < 3 ) {
            $mainRight         = RightsUtilities::getDisplayTextForRight('dataViewing');
            $secondaryRight    = RightsUtilities::getDisplayTextForRight('editSurveyResponses');
            $this->badRights[] = $mainRight . ' - ' . $secondaryRight;
        }
    }

    private function checkDataViewingRights($right, $value)
    {
        $isDataViewingRight = substr_compare($right, 'form-', 0, strlen('form-')) === 0;
        if ( !$isDataViewingRight ) {
            return;
        }
        $this->accountedFor = true;
        $mainRight          = RightsUtilities::getDisplayTextForRight('dataViewing');
        // 0: no access, 2: read only, 1: view and edit, 3: edit survey responses
        if ( $value === '3' && $this->dataViewing < 3 ) {
            $this->badRights[] = $mainRight . ' - ' . RightsUtilities::getDisplayTextForRight('editSurveyResponses');
        } elseif ( $value === '1' && $this->dataViewing < 2 ) {
            $this->badRights[] = $mainRight . ' - ' . RightsUtilities::getDisplayTextForRight('viewAndEdit');
        } elseif ( $value === '2' && $this->dataViewing < 1 ) {
            $this->badRights[] = $mainRight . ' - ' . RightsUtilities::getDisplayTextForRight('readOnly');
        }
    }

    private function checkDataViewingRights2($right)
    {
        $isDataViewingRight = substr_compare($right, 'data_entry', 0, strlen('data_entry')) === 0;
        if ( !$isDataViewingRight ) {
            return;
        }
        $this->accountedFor = true;
        $mainRight          = RightsUtilities::getDisplayTextForRight('dataViewing');
        if ( $right === 'data_entry3' && $this->dataViewing < 3 ) {
            $this->badRights[] = $mainRight . ' - ' . RightsUtilities::getDisplayTextForRight('editSurveyResponses');
        } elseif ( $right === 'data_entry1' && $this->dataViewing < 2 ) {
            $this->badRights[] = $mainRight . ' - ' . RightsUtilities::getDisplayTextForRight('viewAndEdit');
        } elseif ( $right === 'data_entry2' && $this->dataViewing < 1 ) {
            $this->badRights[] = $mainRight . ' - ' . RightsUtilities::getDisplayTextForRight('readOnly');
        }
    }

    private function checkDataExportRights($right, $value)
    {
        $isDataExportRight = substr_compare($right, 'export-form-', 0, strlen('export-form-')) === 0;
        if ( !$isDataExportRight ) {
            return;
        }
        $this->accountedFor = true;
        $mainRight          = RightsUtilities::getDisplayTextForRight('dataExport');
        // 0: no access, 2: deidentified, 3: remove identifiers, 1: full data set
        if ( $value == '1' && $this->dataExport < 3 ) {
            $this->badRights[] = $mainRight . ' - ' . RightsUtilities::getDisplayTextForRight('fullDataSet');
        } elseif ( $value == '3' && $this->dataExport < 2 ) {
            $this->badRights[] = $mainRight . ' - ' . RightsUtilities::getDisplayTextForRight('removeIdentifiers');
        } elseif ( $value == '2' && $this->dataExport < 1 ) {
            $this->badRights[] = $mainRight . ' - ' . RightsUtilities::getDisplayTextForRight('deidentified');
        }
    }

    private function checkDataExportRights2($right)
    {
        $isDataExportRight = substr_compare($right, 'data_export', 0, strlen('data_export')) === 0;
        if ( !$isDataExportRight ) {
            return;
        }
        $this->accountedFor = true;
        $mainRight          = RightsUtilities::getDisplayTextForRight('dataExport');
        // 0: no access, 2: deidentified, 3: remove identifiers, 1: full data set
        if ( $right === 'data_export1' && $this->dataExport < 3 ) {
            $this->badRights[] = $mainRight . ' - ' . RightsUtilities::getDisplayTextForRight('fullDataSet');
        } elseif ( $right === 'data_export3' && $this->dataExport < 2 ) {
            $this->badRights[] = $mainRight . ' - ' . RightsUtilities::getDisplayTextForRight('removeIdentifiers');
        } elseif ( $right === 'data_export2' && $this->dataExport < 1 ) {
            $this->badRights[] = $mainRight . ' - ' . RightsUtilities::getDisplayTextForRight('deidentified');
        }
    }

    private function checkRecordLockingRights($right, $value)
    {
        $isRecordLockRight = $right == 'lock_record';
        if ( !$isRecordLockRight ) {
            return;
        }
        $this->accountedFor = true;
        if ( intval($value) > intval($this->acceptableRights[$right]) ) {
            $rightName         = $value == 2 ? 'lock_record_esignature' : 'lock_record';
            $this->badRights[] = RightsUtilities::getDisplayTextForRight($rightName);
        }
    }

    private function checkDoubleDataRights($right, $value)
    {
        $isDoubleDataRight = $right == 'double_data';
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
        $ddeReview = $this->acceptableRights['double_data_reviewer'] == 1;
        $ddePerson = $this->acceptableRights['double_data_person'] == 1;
        if ( intval($value) == 0 && !$ddeReview ) {
            $this->badRights[] = RightsUtilities::getDisplayTextForRight('double_data_reviewer');
        } elseif ( intval($value) > 0 && !$ddePerson ) {
            $this->badRights[] = RightsUtilities::getDisplayTextForRight('double_data_person');
        }
    }

    private function checkDataResolutionRights($right, $value)
    {
        $isDataResolutionRight = $right == 'data_quality_resolution';
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

    private function checkUserRightsRight($right, $value)
    {
        $isUserRightsRight = $right === 'user_rights';
        if ( !$isUserRightsRight ) {
            return;
        }
        $this->accountedFor = true;
        $value              = $value === 'on' ? '1' : $value; // If REDCap version < 14.1.0, value was binary
        $userRights         = (int) $this->acceptableRights['user_rights'];
        $mainRight          = RightsUtilities::getDisplayTextForRight('user_rights');
        $redcapVersion      = defined('REDCAP_VERSION') ? REDCAP_VERSION : '99.99.99';
        $newVersion         = \REDCap::versionCompare($redcapVersion, '14.1.0') >= 0;
        // Value -> 0: no access, 2: read only, 1: view and edit
        if ( $value == '1' && $userRights != 1 ) {
            $rightName         = $mainRight . ($newVersion ? ' - ' . RightsUtilities::getDisplayTextForRight('fullAccess') : '');
            $this->badRights[] = $rightName;
        } elseif ( $value == '2' && $userRights == 0 ) {
            $rightName         = $mainRight . ($newVersion ? ' - ' . RightsUtilities::getDisplayTextForRight('readOnly') : '');
            $this->badRights[] = $rightName;
        }
    }

    private function checkEmailLoggingRight($right) {
        $isEmailLoggingRight = $right === 'email_logging';
        if ( !$isEmailLoggingRight ) {
            return;
        }
        $this->accountedFor = true;
        if ( !$this->project->isEmailLoggingEnabled() && !$this->checkAllRights ) {
            return;
        }

        $this->checkRight($right);
    }

    private function checkExternalModuleConfigRight($right, $value) {
        $isEMConfigRight = $right === 'external_module_config';
        if ( !$isEMConfigRight ) {
            return;
        }
        $this->accountedFor = true;
        if ( !empty($value) && $this->acceptableRights[$right] == 0 ) {
            $badRight          = RightsUtilities::getDisplayTextForRight('external_module_config');
            $this->badRights[] = $badRight;
        }
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

            $this->checkUserRightsRight($right, $value);
            $this->checkCDPandDDP($right);
            $this->checkMyCapRights($right);
            $this->checkRandomizationRights($right);
            $this->checkStatsAndCharts($right);
            $this->checkSurveyRights($right);
            $this->checkEmailLoggingRight($right);

            $this->checkSurveyEditRights($right, $value);
            $this->checkDataViewingRights($right, $value);
            $this->checkDataExportRights($right, $value);
            $this->checkRecordLockingRights($right, $value);
            $this->checkDataResolutionRights($right, $value);
            $this->checkExternalModuleConfigRight($right, $value);

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

            $this->checkUserRightsRight($right, $value);
            $this->checkCDPandDDP($right);
            $this->checkMyCapRights($right);
            $this->checkRandomizationRights($right);
            $this->checkStatsAndCharts($right);
            $this->checkSurveyRights($right);
            $this->checkEmailLoggingRight($right);

            $this->checkDataViewingRights2($right);
            $this->checkDataExportRights2($right);
            $this->checkRecordLockingRights($right, $value);
            $this->checkDataResolutionRights($right, $value);
            $this->checkExternalModuleConfigRight($right, $value);


            if ( !$this->accountedFor && $this->acceptableRights[$right] == 0 ) {
                $this->badRights[] = RightsUtilities::getDisplayTextForRight($right);
            }
        }
        return array_values(array_unique($this->badRights, SORT_REGULAR));
    }
}