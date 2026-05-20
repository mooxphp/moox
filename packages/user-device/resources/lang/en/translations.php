<?php

return [
    'single' => 'Device',
    'plural' => 'Devices',
    'breadcrumb' => 'Device',
    'title' => 'Device',
    'navigation_label' => 'Devices',
    'navigation_group' => 'Moox User',
    'totalone' => 'Devices',
    'totaltwo' => 'Users',
    'totalthree' => 'Active',
    'created_at' => 'Created at',
    'updated_at' => 'Last login',
    'active' => 'Active',
    'user_type' => 'User Model',
    'username' => 'Username',
    'slug' => 'Slug',

    // Mail
    'mail_subject_new_device' => 'New device login detected',
    'mail_title_new_device' => 'New device registered',
    'mail_greeting' => 'Hello',
    'mail_intro' => 'We detected a new login to your account.',
    'mail_label_device' => 'Device',
    'mail_label_system' => 'System',
    'mail_label_ip' => 'IP address',
    'mail_label_location' => 'Location',
    'mail_if_it_was_you' => 'If this was you, you can ignore this email.',
    'mail_if_it_was_not_you' => 'If this was not you:',
    'mail_step_review_devices' => 'Review your devices',
    'mail_step_change_password' => 'Change your password',
    'mail_step_check_mfa' => 'Enable (or review) multi-factor authentication',
    'mail_cta_trust_device' => 'Trust this device',
    'mail_cta_review_devices' => 'Review devices',
    'mail_outro_secure_account' => 'If this was not you, please secure your account.',

    // Enforcement
    'device_blocked_title' => 'Device confirmation required',
    'device_blocked_body' => 'Please confirm this device using the link from the email we sent you.',

    // Devices
    'device_trusted' => 'Trusted',
    'device_trust' => 'Trust',
    'device_untrust' => 'Untrust',

    'device_trust_modal_heading' => 'Trust this device?',
    'device_trust_modal_description' => 'The user will be able to use this device normally again.',
    'device_trust_success_title' => 'Device trusted',

    'device_untrust_modal_heading' => 'Untrust this device?',
    'device_untrust_modal_description' => 'The user will be blocked on this device until they confirm it again via email.',
    'device_untrust_success_title' => 'Device untrusted',

    'device_delete' => 'Delete',
    'device_delete_modal_heading' => 'Delete this device?',
    'device_delete_modal_description' => 'This removes the device from the user account.',
    'device_delete_success_title' => 'Device deleted',
];
