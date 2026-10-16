export type StaffRole = 'manager' | 'admin';

/**
 * Data akun yang dibagikan ke semua halaman. Sinkron dengan
 * HandleInertiaRequests::sharedUser().
 */
export type User = {
    id: number;
    name: string;
    email: string;
    role: StaffRole;
    role_label: string;
    two_factor_enabled: boolean;
};

export type Abilities = {
    manageStaff: boolean;
    viewActivityLog: boolean;
};

export type Auth = {
    user: User;
    can: Abilities;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
