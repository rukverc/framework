<?php

namespace Dappur\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;

/**
 * Dappur Framework Controller
 * Auto generated by the Dappur CLI at: 2017-06-11 8:42 pm
 */
class AdminSettings extends Controller
{
	public function settingsGlobal(Request $request, Response $response){

        if (!$this->auth->hasAccess('config.view')) {

            $loggedUser = $this->auth->check();
            
            $this->flash('danger', 'You do not have permission to access the settings.');
            $this->logger->addError("Unauthorized Access", array("message" => "Unauthorized access was attempted on the global settings page", "user_id" => $loggedUser['id']));
            return $this->redirect($response, 'dashboard');
            
        }

        $settings = new \Dappur\Dappurware\Settings($this->container);

        $timezones = $settings->getTimezones();
        $theme_list = $settings->getThemeList();
        $bootswatch = $settings->getBootswatch();
        $settings_grouped = $settings->getSettingsByGroup();

        $types = new \Dappur\Model\ConfigTypes;
        $types = $types->orderBy('name')->get();

        $groups = new \Dappur\Model\ConfigGroups;
        $groups = $groups->orderBy('name')->get();

        if ($request->isPost()) {
            if (!$this->auth->hasAccess('config.global')) {

                $this->flash('danger', 'You do not have permission to update settings.');
                $this->logger->addError("Unauthorized Access", array("message" => "Unauthorized access was attempted on the global settings page", "user_id" => $loggedUser['id']));
                return $this->redirect($response, 'settings-global');

            }

            $allPostVars = $request->getParsedBody();

            // Validate Domain
            if (array_key_exists('domain', $allPostVars)){
                $this->validator->validate($request, ['domain' => array('rules' => V::domain(), 'messages' => array('domain' => 'Please enter a valid domain.'))]);
            }

            // Validate Reply To Email
            if (array_key_exists('replyto-email', $allPostVars)){
                $this->validator->validate($request, ['replyto-email' => array('rules' => V::noWhitespace()->email(), 'messages' => array('noWhitespace' => 'Must not contain any spaces.', 'email' => 'Enter a valid email address.'))]);
            }

            // Validate Google Analytics
            if (isset($allPostVars['ga']) && !empty($allPostVars['ga'])){
                $this->validator->validate($request, ['ga' => array('rules' => V::regex('/(UA|YT|MO)-\d+-\d+/'), 'messages' => array('regex' => 'Enter a valid UA Tracking Code'))]);
            }

            // Additional Validation
            foreach ($allPostVars as $key => $value) {
                if (strip_tags($value) != $value) {
                    $this->validator->addError($key, 'Please do not use any HTML Tags');
                    $this->logger->addWarning("possible scripting attack", array("message" => "HTML tags were blocked from being put into the config."));
                }

                if ($key == "theme" && !in_array($value, $theme_list)) {
                    $this->validator->addError($key, 'Not a valid global setting.');
                }
            }


            if ($this->validator->isValid()) {

                foreach ($allPostVars as $key => $value) {
                    $updateRow = new \Dappur\Model\Config;
                    $updateRow->where('name', $key)->update(['value' => $value]);
                }

                $this->flash('success', 'Global settings have been updated successfully.');
                return $this->redirect($response, 'settings-global');
            }

            
        }

        return $this->view->render($response, 'settings-global.twig', array("settingsGrouped" => $settings_grouped, "configTypes" => $types, "configGroups" => $groups, "themeList" => $theme_list, "timezones" => $timezones, "bsThemes" => $bootswatch));

    }

    public function settingsGlobalAdd(Request $request, Response $response){

        if (!$this->auth->hasAccess('config.global')) {

            $loggedUser = $this->auth->check();
            
            $this->flash('danger', 'You do not have permission to add settings.');
            $this->logger->addError("Unauthorized Access", array("message" => "Unauthorized access was attempted on the global settings add page", "user_id" => $loggedUser['id']));
            return $this->redirect($response, 'settings-global');
            
        }


        $allPostVars = $request->getParsedBody();

        $this->validator->validate($request, 
            array(
                'add_name' => array(
                    'rules' => V::slug()->length(4, 32), 
                    'messages' => array(
                        'slug' => 'May only contain lowercase letters, numbers and hyphens.', 
                        'length' => 'Must be between 4 and 32 characters.'
                    )
                ),
                'add_description' => array(
                    'rules' => V::alnum()->length(4, 32), 
                    'messages' => array(
                        'alnum' => 'May only contain letters and numbers.', 
                        'length' => 'Must be between 4 and 32 characters.'
                    )
                )
            )
        );

        $check_config = $config->where('name', '=', $allPostVars['add_name'])->get()->count();
        if ($check_config > 0) {
            $this->validator->addError('add_name', 'Name is already in use.');
        }

        if ($this->validator->isValid()) {

            $configOption = new \Dappur\Model\Config;
            $configOption->name = $allPostVars['add_name'];
            $configOption->description = $allPostVars['add_description'];
            $configOption->type_id = $allPostVars['add_type'];
            $configOption->group_id = $allPostVars['add_group'];
            $configOption->value = $allPostVars['add_value'];
            $configOption->save();

            $this->flash('success', 'Global settings successfully added.');
            return $this->redirect($response, 'settings-global');
        }

        $settings = new \Dappur\Dappurware\Settings($this->container);
        $timezones = $settings->getTimezones();
        $theme_list = $settings->getThemeList();
        $bootswatch = $settings->getBootswatch();
        $settings_grouped = $settings->getSettingsByGroup();

        $types = new \Dappur\Model\ConfigTypes;
        $types = $types->orderBy('name')->get();

        $groups = new \Dappur\Model\ConfigGroups;
        $groups = $groups->orderBy('name')->get();

        return $this->view->render($response, 'settings-global.twig', array("settingsGrouped" => $settings_grouped, "configTypes" => $types, "configGroups" => $groups, "themeList" => $theme_list, "timezones" => $timezones));

    }

    public function settingsGlobalAddGroup(Request $request, Response $response){

        if (!$this->auth->hasAccess('config.group')) {

            $loggedUser = $this->auth->check();
            
            $this->flash('danger', 'You do not have permission to add config groups.');
            $this->logger->addError("Unauthorized Access", array("message" => "Unauthorized access was attempted on the config group add page", "user_id" => $loggedUser['id']));
            return $this->redirect($response, 'settings-global');
            
        }


        $allPostVars = $request->getParsedBody();

        $this->validator->validate($request, 
            array(
                'group_name' => array(
                    'rules' => V::alnum()->length(4, 32), 
                    'messages' => array(
                        'alnum' => 'May only contain lowercase letters, numbers and hyphens.', 
                        'length' => 'Must be between 4 and 32 characters.'
                    )
                )
            )
        );

        $check_group = new \Dappur\Model\ConfigGroups;
        $check_group = $check_group->where('name', '=', $allPostVars['group_name'])->get()->count();
        if ($check_group > 0) {
            $this->validator->addError('group_name', 'Name is already in use.');
        }

        if ($this->validator->isValid()) {

            $configOption = new \Dappur\Model\ConfigGroups;
            $configOption->name = $allPostVars['group_name'];
            $configOption->save();

            $this->flash('success', 'Config group successfully added.');
            return $this->redirect($response, 'settings-global');
        }

        $settings = new \Dappur\Dappurware\Settings($this->container);
        $timezones = $settings->getTimezones();
        $theme_list = $settings->getThemeList();
        $bootswatch = $settings->getBootswatch();

        $config = new \Dappur\Model\Config;
        $global_config = $config->get();

        return $this->view->render($response, 'settings-global.twig', array("settingsGrouped" => $settings_grouped, "configTypes" => $types, "configGroups" => $groups, "themeList" => $theme_list, "timezones" => $timezones));

    }

    public function settingsGlobalDeleteGroup(Request $request, Response $response){

        if (!$this->auth->hasAccess('config.group')) {

            $loggedUser = $this->auth->check();
            
            $this->flash('danger', 'You do not have permission to add config groups.');
            $this->logger->addError("Unauthorized Access", array("message" => "Unauthorized access was attempted on the config group add page", "user_id" => $loggedUser['id']));
            return $this->redirect($response, 'settings-global');
            
        }


        $allPostVars = $request->getParsedBody();

        $check_group = new \Dappur\Model\ConfigGroups;
        $check_group = $check_group->find($allPostVars['group_id']);

        if (!$check_group) {
            $this->flash('danger', 'Group does not exist.');
            return $this->redirect($response, 'settings-global');
        }else{
            $check_config = new \Dappur\Model\Config;
            $check_config = $check_config->where('group_id', '=', $allPostVars['group_id'])->get()->count();

            if ($check_config > 0) {
                $this->flash('danger', 'You cannot delete a group with config items in it.');
                return $this->redirect($response, 'settings-global');
            }else{
                $check_group->delete();
                $this->flash('success', 'Group was successfully deleted.');
                return $this->redirect($response, 'settings-global');
            }
        }

    }

    public function settingsDeveloper(Request $request, Response $response){

    	if (!$this->auth->hasAccess('developer.settings')) {

            $loggedUser = $this->auth->check();
            
            $this->flash('danger', 'You do not have permission to access the developer settings.');
            $this->logger->addError("Unauthorized Access", array("message" => "Unauthorized access was attempted on the developer settings page", "user_id" => $loggedUser['id']));
            return $this->redirect($response, 'dashboard');
            
        }

        $settings = new \Dappur\Dappurware\Settings($this->container);
        $settings_file = $settings->getSettingsFile();

        $allPostVars = $request->getParsedBody();

        /*
        if ($request->isPost()) {
            if ($allPostVars['add_type'] == "string") {
                // Check for HTML Tags
                if (strip_tags($allPostVars['add_value']) != $allPostVars['add_value']) {
                    $this->validator->addError('add_value', 'Please do not use any HTML Tags');
                    $this->logger->addWarning("possible scripting attack", array("message" => "HTML tags were blocked from being put into the config."));
                }
            } else {
                $this->validator->addError('add_value', 'Not a valid global setting.');
            }

            $check_config = $config->where('name', '=', $allPostVars['add_name'])->get()->count();
            if ($check_config > 0) {
                $this->validator->addError('add_name', 'Name is already in use.');
            }

            if ($this->validator->isValid()) {

                $configOption = new \Dappur\Model\Config;
                $configOption->name = $allPostVars['add_name'];
                $configOption->description = $allPostVars['add_description'];
                $configOption->type = $allPostVars['add_type'];
                $configOption->value = $allPostVars['add_value'];
                $configOption->save();


                $this->flash('success', 'Global settings successfully added.');
                return $this->redirect($response, 'settings-global');
            }
        }
        */
    	return $this->view->render($response, 'settings-developer.twig', array("settingsFile" => $settings_file, "postVars" => $allPostVars));
    }

}