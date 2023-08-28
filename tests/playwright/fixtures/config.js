exports.config = {
    redcapVersion: 'redcap_v13.1.27',
    redcapUrl: 'http://localhost',
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
    }
}