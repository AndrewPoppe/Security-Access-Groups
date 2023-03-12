<?php

namespace YaleREDCap\SystemUserRights;

class APIHandler
{
    private $module;
    private $post;
    private $token;
    private $data;
    function __construct(SystemUserRights $module, array $post)
    {
        $this->module = $module;
        $this->post = $post;
        $this->token = $this->post["token"];
        $this->data = json_decode($this->post["data"] ?? "{}", true);
        $this->module->log('started');
    }

    public function handleRequest()
    {
    }

    public function shouldProcess()
    {
        $rights = $this->getUserRightsFromToken($_POST["token"]) ?? [];
        $project_id = $rights["project_id"];
        $prefix = $this->module->getModuleDirectoryPrefix();
        $isModuleEnabledInProject = (bool) $this->module->isModuleEnabled($prefix, $project_id);
        $isApiUserRightsMethod = in_array($_POST["content"], ["user", "userRole", "userRoleMapping"], true);
        $dataImported = isset($_POST["data"]);

        return $isModuleEnabledInProject && $isApiUserRightsMethod && $dataImported;
    }

    private function getUserRightsFromToken()
    {
        $sql = "SELECT * FROM redcap_user_rights WHERE api_token = ?";
        $rights = [];
        try {
            $result = $this->query($sql, [$this->token]);
            $rights = $result->fetch_assoc();
        } catch (\Throwable $e) {
            $this->log('Error getting user rights from API token', ["error" => $e->getMessage()]);
        } finally {
            return $rights;
        }
    }
}
