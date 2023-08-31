exports.config = {
    redcapVersion: 'redcap_v13.1.27',
    redcapUrl: 'http://localhost:13740',
    projects: {
        UI_Project: {
            projectName: 'SAG EM - UI Test Project',
            pid: null
        },
        CSV_Project: {
            projectName: 'SAG EM - CSV Test Project',
            pid: null
        },
        API_Project: {
            projectName: 'SAG EM - API Test Project',
            pid: null
        }
    },
    sags: {
        nothingSag: {
            id: null,
        },
        everythingSag: {
            id: null,
        }
    },
    roles: {
        Test: {
            id: null,
            name: 'Test',
            uniqueRoleName: null
        }
    },
    users: {
        EverythingUser: {
            username: 'bob',
            password: 'password'
        },
        NothingUser: {
            username: 'alice',
            password: 'password'
        },
        AdminUser: {
            username: 'admin',
            password: 'password'
        }
    },
    system_em_framework_config: {
        languages: [
            'English',
            'Arabic',
            'Bangla',
            'Chinese',
            'French',
            'German',
            'Hindi',
            'Italian',
            'Portuguese',
            'Spanish',
            'Ukrainian',
            'Urdu'
        ],
        default_options: [
            "Language file",
            "Enable module on all projects by default",
            "Make module discoverable by users",
            "Allow non-admins to enable this module on projects",
            "Hide this module from non-admins in the list of enabled modules on each project"
        ],
        custom_options: [
            "User Alert Email Default Subject",
            "User Alert Email Default Body",
            "User Reminder Alert Email Default Subject",
            "User Reminder Email Default Body",
            "User Rights Holders Alert Email Default Subject",
            "User Rights Holders Alert Email Default Body",
            "User Rights Holders Reminder Alert Email Default Subject",
            "User Rights Holders Reminder Alert Email Default Body",
            "User Expiration Alert Email Default Subject",
            "User Expiration Alert Email Default Body",
            "User Rights Holders Alert Email Default Subject",
            "User Rights Holders Alert Email Default Body"
        ]
    }
}