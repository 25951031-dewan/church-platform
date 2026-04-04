# User Auth Dropdown & Profile Management — Implementation Plan

> **Approach:** Hybrid (Sngine backend patterns + BeMusic frontend design)  
> **Goal:** Create comprehensive user dropdown with 2FA, spiritual profiles, custom fields, session management  
> **Architecture:** Sngine's trait-based backend + BeMusic's React components + Church-specific customization

---

## Feature Overview

### Core Features (Sngine-inspired)
- **User Avatar Dropdown** - Profile photo, name, role badge
- **Two-Factor Authentication** - Google Authenticator TOTP setup
- **Spiritual Profile & Bio** - Custom fields enabled by admin settings  
- **Login Session Management** - Device tracking, active sessions
- **Security Settings** - Password change, email change, privacy controls
- **Profile Photo Management** - Upload, crop, delete profile pictures
- **Account Settings** - Personal info, church membership, notifications

### Church-Specific Additions
- **Ministry Involvement** - Roles, departments, volunteer history
- **Spiritual Journey** - Baptism, salvation date, growth milestones
- **Prayer Preferences** - Prayer language, ministry interests
- **Church Directory** - Visibility settings, contact permissions

---

## Backend Implementation (Sngine Patterns)

### Database Schema

#### User Profile Extensions
```sql
-- Add to users table
ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255);
ALTER TABLE users ADD COLUMN two_factor_enabled BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN two_factor_secret VARCHAR(255);
ALTER TABLE users ADD COLUMN bio TEXT;
ALTER TABLE users ADD COLUMN spiritual_profile JSON;

-- User sessions table (like Sngine users_sessions)
CREATE TABLE user_sessions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT REFERENCES users(id) ON DELETE CASCADE,
    session_id VARCHAR(255) UNIQUE,
    device_name VARCHAR(255),
    device_type VARCHAR(50), -- mobile, desktop, tablet
    ip_address VARCHAR(45),
    user_agent TEXT,
    location VARCHAR(255), -- City, Country
    is_current BOOLEAN DEFAULT FALSE,
    last_activity TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Custom profile fields (admin-configurable)
CREATE TABLE user_custom_fields (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    church_id BIGINT REFERENCES churches(id),
    field_name VARCHAR(100),
    field_type ENUM('text', 'textarea', 'select', 'date', 'boolean', 'number'),
    field_options JSON, -- For select fields
    is_required BOOLEAN DEFAULT FALSE,
    is_spiritual BOOLEAN DEFAULT FALSE, -- Spiritual profile field
    display_order INTEGER DEFAULT 0,
    is_enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- User custom field values
CREATE TABLE user_custom_field_values (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT REFERENCES users(id) ON DELETE CASCADE,
    field_id BIGINT REFERENCES user_custom_fields(id) ON DELETE CASCADE,
    field_value TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(user_id, field_id)
);

-- Ministry involvement
CREATE TABLE user_ministry_roles (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT REFERENCES users(id) ON DELETE CASCADE,
    church_id BIGINT REFERENCES churches(id),
    ministry_name VARCHAR(100),
    role_title VARCHAR(100),
    start_date DATE,
    end_date DATE NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Models & Services (Following Sngine Traits Pattern)

#### User Model Extensions
```php
// app/Models/User.php (extend existing)
class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory, BelongsToChurch;
    
    // Add spiritual profile and 2FA support
    protected $fillable = [
        // existing fields...
        'profile_photo', 'bio', 'spiritual_profile',
        'two_factor_enabled', 'two_factor_secret'
    ];
    
    protected $casts = [
        'spiritual_profile' => 'json',
        'two_factor_enabled' => 'boolean',
    ];
    
    // Relationships
    public function customFieldValues()
    {
        return $this->hasMany(UserCustomFieldValue::class);
    }
    
    public function sessions()
    {
        return $this->hasMany(UserSession::class);
    }
    
    public function ministryRoles()
    {
        return $this->hasMany(UserMinistryRole::class);
    }
}
```

#### New Models
```php
// app/Models/UserCustomField.php
class UserCustomField extends Model
{
    use BelongsToChurch;
    
    protected $fillable = [
        'church_id', 'field_name', 'field_type', 'field_options',
        'is_required', 'is_spiritual', 'display_order', 'is_enabled'
    ];
    
    protected $casts = [
        'field_options' => 'json',
        'is_required' => 'boolean',
        'is_spiritual' => 'boolean',
        'is_enabled' => 'boolean',
    ];
}

// app/Models/UserSession.php
class UserSession extends Model
{
    protected $fillable = [
        'user_id', 'session_id', 'device_name', 'device_type',
        'ip_address', 'user_agent', 'location', 'is_current',
        'last_activity'
    ];
    
    protected $casts = [
        'is_current' => 'boolean',
        'last_activity' => 'datetime',
    ];
}
```

### Services (Sngine-style)

#### TwoFactorService
```php
// app/Services/TwoFactorService.php
class TwoFactorService
{
    public function generateSecret(): string
    public function generateQrCode(User $user): string
    public function verifyCode(User $user, string $code): bool
    public function enable(User $user, string $code): bool
    public function disable(User $user): bool
    public function getBackupCodes(User $user): array
}
```

#### UserProfileService  
```php
// app/Services/UserProfileService.php
class UserProfileService
{
    public function updateProfile(User $user, array $data): User
    public function updateSpiritualProfile(User $user, array $data): User
    public function updateCustomFields(User $user, array $fields): void
    public function uploadProfilePhoto(User $user, UploadedFile $file): string
    public function getProfileCompleteness(User $user): int
}
```

#### SessionService
```php  
// app/Services/SessionService.php
class SessionService
{
    public function trackSession(User $user, Request $request): UserSession
    public function getActiveSessions(User $user): Collection
    public function revokeSession(User $user, string $sessionId): bool
    public function revokeAllOtherSessions(User $user): int
    public function updateLastActivity(User $user): void
}
```

### API Controllers (BeMusic-style)

#### UserProfileController
```php
// app/Http/Controllers/Api/UserProfileController.php
class UserProfileController extends Controller
{
    public function show(Request $request)           // GET /api/v1/user/profile
    public function update(UpdateProfileRequest $request) // PUT /api/v1/user/profile
    public function uploadPhoto(Request $request)    // POST /api/v1/user/profile/photo
    public function deletePhoto(Request $request)    // DELETE /api/v1/user/profile/photo
}

// app/Http/Controllers/Api/UserSecurityController.php  
class UserSecurityController extends Controller
{
    public function changePassword(ChangePasswordRequest $request) // PUT /api/v1/user/password
    public function enable2FA(Request $request)      // POST /api/v1/user/2fa/enable
    public function disable2FA(Request $request)     // POST /api/v1/user/2fa/disable
    public function generate2FAQrCode()             // GET /api/v1/user/2fa/qr-code
    public function verify2FA(Request $request)      // POST /api/v1/user/2fa/verify
}

// app/Http/Controllers/Api/UserSessionsController.php
class UserSessionsController extends Controller  
{
    public function index(Request $request)          // GET /api/v1/user/sessions
    public function revoke(Request $request, $sessionId) // DELETE /api/v1/user/sessions/{id}
    public function revokeAll(Request $request)      // DELETE /api/v1/user/sessions
}
```

---

## Frontend Implementation (BeMusic Design)

### User Dropdown Component
```typescript
// resources/client/common/auth/UserDropdown.tsx
interface UserDropdownProps {
  user: User;
  onClose: () => void;
}

interface User {
  id: number;
  name: string;
  email: string;
  profile_photo?: string;
  role: string;
  church: {
    name: string;
    logo?: string;
  };
  two_factor_enabled: boolean;
  profile_completeness: number;
  spiritual_profile?: {
    baptism_date?: string;
    salvation_date?: string;
    ministry_interests?: string[];
  };
}

const UserDropdown: React.FC<UserDropdownProps> = ({ user, onClose }) => {
  return (
    <div className="absolute right-0 top-12 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50">
      {/* Profile Header */}
      <div className="p-4 border-b border-gray-200 dark:border-gray-700">
        <div className="flex items-center gap-3">
          <Avatar src={user.profile_photo} name={user.name} size="lg" />
          <div className="flex-1">
            <h3 className="font-semibold text-gray-900 dark:text-white">
              {user.name}
            </h3>
            <p className="text-sm text-gray-600 dark:text-gray-400">
              {user.email}
            </p>
            <div className="flex items-center gap-2 mt-1">
              <Badge variant={user.role === 'admin' ? 'primary' : 'secondary'}>
                {user.role}
              </Badge>
              {user.two_factor_enabled && (
                <Badge variant="success" className="text-xs">
                  2FA ✓
                </Badge>
              )}
            </div>
          </div>
        </div>
        
        {/* Profile Completeness */}
        <div className="mt-3">
          <div className="flex justify-between text-sm">
            <span className="text-gray-600 dark:text-gray-400">Profile</span>
            <span className="text-gray-900 dark:text-white">
              {user.profile_completeness}%
            </span>
          </div>
          <ProgressBar 
            value={user.profile_completeness} 
            className="mt-1 h-2"
          />
        </div>
      </div>

      {/* Menu Items */}
      <div className="py-2">
        <DropdownItem 
          icon={User} 
          label="Profile & Bio"
          onClick={() => navigate('/profile/edit')}
        />
        <DropdownItem 
          icon={Heart} 
          label="Spiritual Profile"
          onClick={() => navigate('/profile/spiritual')}
        />
        <DropdownItem 
          icon={Shield} 
          label="Security & 2FA"
          onClick={() => navigate('/profile/security')}
        />
        <DropdownItem 
          icon={Smartphone} 
          label="Active Sessions"
          onClick={() => navigate('/profile/sessions')}
        />
        <DropdownItem 
          icon={Church} 
          label="Ministry Roles"
          onClick={() => navigate('/profile/ministry')}
        />
        
        <div className="border-t border-gray-200 dark:border-gray-700 my-2" />
        
        <DropdownItem 
          icon={Settings} 
          label="Settings"
          onClick={() => navigate('/settings')}
        />
        <DropdownItem 
          icon={HelpCircle} 
          label="Help & Support"
          onClick={() => navigate('/help')}
        />
        
        <div className="border-t border-gray-200 dark:border-gray-700 my-2" />
        
        <DropdownItem 
          icon={LogOut} 
          label="Sign Out"
          onClick={handleSignOut}
          className="text-red-600 hover:text-red-700 hover:bg-red-50"
        />
      </div>
    </div>
  );
};
```

### Profile Settings Pages

#### Main Profile Edit Page
```typescript
// resources/client/auth/profile/ProfileEditPage.tsx
const ProfileEditPage: React.FC = () => {
  const { user } = useAuth();
  const [activeTab, setActiveTab] = useState('basic');
  
  return (
    <div className="max-w-4xl mx-auto p-6">
      <PageHeader 
        title="Profile Settings"
        subtitle="Manage your personal information and church profile"
      />
      
      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <TabsList>
          <TabsTrigger value="basic">Basic Info</TabsTrigger>
          <TabsTrigger value="spiritual">Spiritual Profile</TabsTrigger>
          <TabsTrigger value="ministry">Ministry Involvement</TabsTrigger>
          <TabsTrigger value="privacy">Privacy</TabsTrigger>
        </TabsList>
        
        <TabsContent value="basic">
          <BasicProfileForm user={user} />
        </TabsContent>
        
        <TabsContent value="spiritual">
          <SpiritualProfileForm user={user} />
        </TabsContent>
        
        <TabsContent value="ministry">
          <MinistryRolesForm user={user} />
        </TabsContent>
        
        <TabsContent value="privacy">
          <PrivacySettingsForm user={user} />
        </TabsContent>
      </Tabs>
    </div>
  );
};
```

#### Security Settings Page
```typescript
// resources/client/auth/profile/SecurityPage.tsx
const SecurityPage: React.FC = () => {
  return (
    <div className="max-w-2xl mx-auto p-6 space-y-6">
      <PageHeader 
        title="Security Settings"
        subtitle="Manage your account security and login preferences"
      />
      
      <Card>
        <CardHeader>
          <CardTitle>Two-Factor Authentication</CardTitle>
          <CardDescription>
            Add an extra layer of security to your account
          </CardDescription>
        </CardHeader>
        <CardContent>
          <TwoFactorSetup />
        </CardContent>
      </Card>
      
      <Card>
        <CardHeader>
          <CardTitle>Change Password</CardTitle>
        </CardHeader>
        <CardContent>
          <ChangePasswordForm />
        </CardContent>
      </Card>
      
      <Card>
        <CardHeader>
          <CardTitle>Active Sessions</CardTitle>
          <CardDescription>
            Manage devices that are signed into your account
          </CardDescription>
        </CardHeader>
        <CardContent>
          <ActiveSessionsList />
        </CardContent>
      </Card>
    </div>
  );
};
```

### Two-Factor Authentication Component
```typescript
// resources/client/auth/components/TwoFactorSetup.tsx
const TwoFactorSetup: React.FC = () => {
  const [step, setStep] = useState<'disabled' | 'setup' | 'verify' | 'enabled'>('disabled');
  const [qrCode, setQrCode] = useState<string>('');
  
  const handleEnable2FA = async () => {
    try {
      const response = await apiClient.get('/user/2fa/qr-code');
      setQrCode(response.qr_code);
      setStep('setup');
    } catch (error) {
      toast.error('Failed to generate QR code');
    }
  };
  
  const handleVerify = async (code: string) => {
    try {
      await apiClient.post('/user/2fa/enable', { code });
      setStep('enabled');
      toast.success('Two-factor authentication enabled successfully');
    } catch (error) {
      toast.error('Invalid verification code');
    }
  };
  
  return (
    <div className="space-y-4">
      {step === 'disabled' && (
        <div>
          <p className="text-gray-600 mb-4">
            Two-factor authentication is currently disabled. Enable it to secure your account.
          </p>
          <Button onClick={handleEnable2FA}>
            Enable 2FA
          </Button>
        </div>
      )}
      
      {step === 'setup' && (
        <div>
          <h4 className="font-medium mb-2">Scan QR Code</h4>
          <p className="text-sm text-gray-600 mb-4">
            Scan this QR code with Google Authenticator or similar app:
          </p>
          <div className="flex justify-center mb-4">
            <img src={qrCode} alt="2FA QR Code" className="border rounded" />
          </div>
          <VerificationCodeInput onVerify={handleVerify} />
        </div>
      )}
      
      {step === 'enabled' && (
        <TwoFactorManagement />
      )}
    </div>
  );
};
```

---

## API Routes

```php
// routes/api.php - User Profile & Auth routes
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Profile Management
    Route::prefix('user')->group(function () {
        Route::get('/profile', [UserProfileController::class, 'show']);
        Route::put('/profile', [UserProfileController::class, 'update']);
        Route::post('/profile/photo', [UserProfileController::class, 'uploadPhoto']);
        Route::delete('/profile/photo', [UserProfileController::class, 'deletePhoto']);
        
        // Security
        Route::put('/password', [UserSecurityController::class, 'changePassword']);
        Route::post('/2fa/enable', [UserSecurityController::class, 'enable2FA']);
        Route::post('/2fa/disable', [UserSecurityController::class, 'disable2FA']);
        Route::get('/2fa/qr-code', [UserSecurityController::class, 'generate2FAQrCode']);
        Route::post('/2fa/verify', [UserSecurityController::class, 'verify2FA']);
        
        // Sessions
        Route::get('/sessions', [UserSessionsController::class, 'index']);
        Route::delete('/sessions/{sessionId}', [UserSessionsController::class, 'revoke']);
        Route::delete('/sessions', [UserSessionsController::class, 'revokeAll']);
        
        // Custom Fields
        Route::get('/custom-fields', [UserCustomFieldsController::class, 'index']);
        Route::put('/custom-fields', [UserCustomFieldsController::class, 'update']);
    });
});

// Admin Routes for Custom Fields Management
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::apiResource('custom-fields', CustomFieldsController::class);
});
```

---

## Implementation Timeline

### Phase 1: Backend Foundation (Week 1)
- [ ] Database migrations for user profile extensions
- [ ] User model updates and relationships  
- [ ] Basic services (UserProfileService, SessionService)
- [ ] API controllers and routes
- [ ] Profile photo upload functionality

### Phase 2: Security Features (Week 1.5)
- [ ] Two-factor authentication service
- [ ] Session management system
- [ ] Password change functionality
- [ ] Security middleware and validation

### Phase 3: Frontend Components (Week 2)
- [ ] User dropdown component
- [ ] Profile settings pages (tabbed interface)
- [ ] Two-factor setup component
- [ ] Session management UI
- [ ] Profile photo upload/crop component

### Phase 4: Spiritual Profile & Custom Fields (Week 2.5)
- [ ] Custom fields admin interface
- [ ] Spiritual profile forms
- [ ] Ministry roles management
- [ ] Privacy settings
- [ ] Profile completeness tracking

### Phase 5: Integration & Polish (Week 3)
- [ ] Church-specific customizations
- [ ] Admin settings for profile fields
- [ ] User permissions and privacy controls
- [ ] Mobile responsive design
- [ ] Testing and documentation

---

## Church-Specific Customizations

### Admin-Configurable Profile Fields
- Spiritual milestones (baptism, salvation dates)
- Ministry interests and availability  
- Prayer preferences and languages
- Emergency contact information
- Church directory visibility settings

### Permission Levels
- **Public** - Visible to all church members
- **Members Only** - Visible to church members only  
- **Leadership** - Visible to church leadership
- **Private** - Visible to user and admins only

### Integration Points
- Church member directory
- Ministry team management
- Prayer request system
- Event participation tracking
- Volunteer opportunity matching

This hybrid approach gives you the robust backend patterns from Sngine with the clean, modern frontend design from BeMusic, perfectly tailored for church community needs!