import React, { useState, useEffect } from 'react';
import { Users, Plus, X, Award, Calendar, ChevronDown } from 'lucide-react';
import { useForm, useFieldArray } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { toast } from 'react-hot-toast';
import { apiClient } from '@/lib/api-client';

const ministryRoleSchema = z.object({
  current_roles: z.array(z.object({
    ministry_id: z.string().min(1, 'Ministry is required'),
    role_title: z.string().min(1, 'Role title is required'),
    start_date: z.string().min(1, 'Start date is required'),
    end_date: z.string().optional(),
    description: z.string().optional(),
    is_leadership: z.boolean().default(false),
    is_public: z.boolean().default(true)
  })),
  past_roles: z.array(z.object({
    ministry_id: z.string().min(1, 'Ministry is required'),
    role_title: z.string().min(1, 'Role title is required'),
    start_date: z.string().min(1, 'Start date is required'),
    end_date: z.string().min(1, 'End date is required'),
    description: z.string().optional(),
    was_leadership: z.boolean().default(false),
    is_public: z.boolean().default(true)
  })),
  availability: z.object({
    weekly_hours: z.string().optional(),
    preferred_times: z.array(z.string()).default([]),
    contact_preference: z.enum(['email', 'phone', 'both']).default('email'),
    notes: z.string().optional()
  }),
  skills: z.array(z.string()).default([]),
  certifications: z.array(z.object({
    name: z.string().min(1, 'Certification name is required'),
    issuer: z.string().optional(),
    date_earned: z.string().optional(),
    expiry_date: z.string().optional(),
    credential_id: z.string().optional()
  })).default([])
});

type MinistryRoleFormData = z.infer<typeof ministryRoleSchema>;

interface Ministry {
  id: string;
  name: string;
  department: string;
}

interface MinistryRolesTabProps {
  onSave?: (data: MinistryRoleFormData) => Promise<void>;
  initialData?: Partial<MinistryRoleFormData>;
}

const MinistryRolesTab: React.FC<MinistryRolesTabProps> = ({ onSave, initialData }) => {
  const [ministries, setMinistries] = useState<Ministry[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [sectionsOpen, setSectionsOpen] = useState({
    current: true,
    past: false,
    availability: false,
    skills: false,
    certifications: false
  });

  const form = useForm<MinistryRoleFormData>({
    resolver: zodResolver(ministryRoleSchema),
    defaultValues: {
      current_roles: [],
      past_roles: [],
      availability: {
        weekly_hours: '',
        preferred_times: [],
        contact_preference: 'email',
        notes: ''
      },
      skills: [],
      certifications: [],
      ...initialData
    }
  });

  const { fields: currentRoles, append: addCurrentRole, remove: removeCurrentRole } = useFieldArray({
    control: form.control,
    name: 'current_roles'
  });

  const { fields: pastRoles, append: addPastRole, remove: removePastRole } = useFieldArray({
    control: form.control,
    name: 'past_roles'
  });

  const { fields: certifications, append: addCertification, remove: removeCertification } = useFieldArray({
    control: form.control,
    name: 'certifications'
  });

  useEffect(() => {
    loadMinistries();
  }, []);

  const loadMinistries = async () => {
    setIsLoading(true);
    try {
      const response = await apiClient.get('/ministries');
      setMinistries(response.data || []);
    } catch (error) {
      toast.error('Failed to load ministries');
    } finally {
      setIsLoading(false);
    }
  };

  const handleSubmit = async (data: MinistryRoleFormData) => {
    setIsSaving(true);
    try {
      if (onSave) {
        await onSave(data);
      } else {
        await apiClient.put('/user/ministry-roles', data);
      }
      toast.success('Ministry roles updated successfully');
    } catch (error) {
      toast.error('Failed to update ministry roles');
    } finally {
      setIsSaving(false);
    }
  };

  const toggleSection = (section: keyof typeof sectionsOpen) => {
    setSectionsOpen(prev => ({ ...prev, [section]: !prev[section] }));
  };

  const addSkill = (skill: string) => {
    if (skill.trim() && !form.getValues('skills').includes(skill.trim())) {
      const currentSkills = form.getValues('skills');
      form.setValue('skills', [...currentSkills, skill.trim()]);
    }
  };

  const removeSkill = (skillToRemove: string) => {
    const currentSkills = form.getValues('skills');
    form.setValue('skills', currentSkills.filter(skill => skill !== skillToRemove));
  };

  const preferredTimes = [
    'Sunday Morning', 'Sunday Evening', 'Monday Morning', 'Monday Evening',
    'Tuesday Morning', 'Tuesday Evening', 'Wednesday Morning', 'Wednesday Evening',
    'Thursday Morning', 'Thursday Evening', 'Friday Morning', 'Friday Evening',
    'Saturday Morning', 'Saturday Evening', 'Weekday Mornings', 'Weekday Evenings',
    'Weekend Mornings', 'Weekend Evenings', 'Flexible'
  ];

  const commonSkills = [
    'Leadership', 'Teaching', 'Music/Worship', 'Audio/Visual', 'Photography',
    'Graphic Design', 'Web Development', 'Marketing', 'Event Planning', 'Counseling',
    'Youth Work', "Children's Ministry", 'Senior Care', 'Hospitality', 'Facilities',
    'Financial Management', 'Administration', 'Prayer Ministry', 'Outreach'
  ];

  return (
    <form onSubmit={form.handleSubmit(handleSubmit)} className="space-y-6">
      {/* Current Roles */}
      <Collapsible open={sectionsOpen.current} onOpenChange={() => toggleSection('current')}>
        <Card>
          <CollapsibleTrigger asChild>
            <CardHeader className="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
              <CardTitle className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <Users className="w-5 h-5" />
                  Current Ministry Roles
                  {currentRoles.length > 0 && (
                    <Badge variant="secondary">{currentRoles.length}</Badge>
                  )}
                </div>
                <ChevronDown className={`w-5 h-5 transition-transform ${sectionsOpen.current ? 'rotate-180' : ''}`} />
              </CardTitle>
            </CardHeader>
          </CollapsibleTrigger>
          <CollapsibleContent>
            <CardContent className="space-y-4">
              {currentRoles.map((field, index) => (
                <Card key={field.id} className="border-l-4 border-l-blue-500">
                  <CardContent className="p-4 space-y-4">
                    <div className="flex items-center justify-between">
                      <h4 className="font-medium text-gray-900 dark:text-white">Role {index + 1}</h4>
                      <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => removeCurrentRole(index)}
                      >
                        <X className="w-4 h-4" />
                      </Button>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <Label htmlFor={`current_roles.${index}.ministry_id`}>Ministry</Label>
                        <Select onValueChange={(value) => form.setValue(`current_roles.${index}.ministry_id`, value)}>
                          <SelectTrigger>
                            <SelectValue placeholder="Select ministry..." />
                          </SelectTrigger>
                          <SelectContent>
                            {ministries.map((ministry) => (
                              <SelectItem key={ministry.id} value={ministry.id}>
                                {ministry.name} - {ministry.department}
                              </SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                        {form.formState.errors.current_roles?.[index]?.ministry_id && (
                          <p className="text-sm text-red-600 mt-1">
                            {form.formState.errors.current_roles[index]?.ministry_id?.message}
                          </p>
                        )}
                      </div>

                      <div>
                        <Label htmlFor={`current_roles.${index}.role_title`}>Role Title</Label>
                        <Input
                          {...form.register(`current_roles.${index}.role_title`)}
                          placeholder="e.g., Worship Leader, Youth Pastor"
                        />
                        {form.formState.errors.current_roles?.[index]?.role_title && (
                          <p className="text-sm text-red-600 mt-1">
                            {form.formState.errors.current_roles[index]?.role_title?.message}
                          </p>
                        )}
                      </div>

                      <div>
                        <Label htmlFor={`current_roles.${index}.start_date`}>Start Date</Label>
                        <Input
                          type="date"
                          {...form.register(`current_roles.${index}.start_date`)}
                        />
                      </div>

                      <div>
                        <Label htmlFor={`current_roles.${index}.end_date`}>End Date (Optional)</Label>
                        <Input
                          type="date"
                          {...form.register(`current_roles.${index}.end_date`)}
                        />
                      </div>
                    </div>

                    <div>
                      <Label htmlFor={`current_roles.${index}.description`}>Description</Label>
                      <Textarea
                        {...form.register(`current_roles.${index}.description`)}
                        placeholder="Describe your responsibilities and achievements..."
                        rows={3}
                      />
                    </div>

                    <div className="flex items-center gap-4">
                      <label className="flex items-center gap-2">
                        <input
                          type="checkbox"
                          {...form.register(`current_roles.${index}.is_leadership`)}
                          className="rounded border-gray-300"
                        />
                        <span className="text-sm">Leadership Position</span>
                      </label>

                      <label className="flex items-center gap-2">
                        <input
                          type="checkbox"
                          {...form.register(`current_roles.${index}.is_public`)}
                          className="rounded border-gray-300"
                          defaultChecked
                        />
                        <span className="text-sm">Show Publicly</span>
                      </label>
                    </div>
                  </CardContent>
                </Card>
              ))}

              <Button
                type="button"
                variant="outline"
                onClick={() => addCurrentRole({
                  ministry_id: '',
                  role_title: '',
                  start_date: '',
                  end_date: '',
                  description: '',
                  is_leadership: false,
                  is_public: true
                })}
                className="w-full"
              >
                <Plus className="w-4 h-4 mr-2" />
                Add Current Role
              </Button>
            </CardContent>
          </CollapsibleContent>
        </Card>
      </Collapsible>

      {/* Past Roles */}
      <Collapsible open={sectionsOpen.past} onOpenChange={() => toggleSection('past')}>
        <Card>
          <CollapsibleTrigger asChild>
            <CardHeader className="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
              <CardTitle className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <Award className="w-5 h-5" />
                  Past Ministry Experience
                  {pastRoles.length > 0 && (
                    <Badge variant="secondary">{pastRoles.length}</Badge>
                  )}
                </div>
                <ChevronDown className={`w-5 h-5 transition-transform ${sectionsOpen.past ? 'rotate-180' : ''}`} />
              </CardTitle>
            </CardHeader>
          </CollapsibleTrigger>
          <CollapsibleContent>
            <CardContent className="space-y-4">
              {pastRoles.length === 0 ? (
                <p className="text-center text-gray-500 dark:text-gray-400 py-4">
                  No past ministry roles added yet
                </p>
              ) : (
                pastRoles.map((field, index) => (
                  <Card key={field.id} className="border-l-4 border-l-gray-400">
                    <CardContent className="p-4 space-y-4">
                      <div className="flex items-center justify-between">
                        <h4 className="font-medium text-gray-900 dark:text-white">Past Role {index + 1}</h4>
                        <Button
                          type="button"
                          variant="outline"
                          size="sm"
                          onClick={() => removePastRole(index)}
                        >
                          <X className="w-4 h-4" />
                        </Button>
                      </div>
                      {/* Similar fields to current roles but with required end_date */}
                    </CardContent>
                  </Card>
                ))
              )}

              <Button
                type="button"
                variant="outline"
                onClick={() => addPastRole({
                  ministry_id: '',
                  role_title: '',
                  start_date: '',
                  end_date: '',
                  description: '',
                  was_leadership: false,
                  is_public: true
                })}
                className="w-full"
              >
                <Plus className="w-4 h-4 mr-2" />
                Add Past Role
              </Button>
            </CardContent>
          </CollapsibleContent>
        </Card>
      </Collapsible>

      {/* Skills & Interests */}
      <Collapsible open={sectionsOpen.skills} onOpenChange={() => toggleSection('skills')}>
        <Card>
          <CollapsibleTrigger asChild>
            <CardHeader className="cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
              <CardTitle className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <Award className="w-5 h-5" />
                  Skills & Interests
                  {form.watch('skills').length > 0 && (
                    <Badge variant="secondary">{form.watch('skills').length}</Badge>
                  )}
                </div>
                <ChevronDown className={`w-5 h-5 transition-transform ${sectionsOpen.skills ? 'rotate-180' : ''}`} />
              </CardTitle>
            </CardHeader>
          </CollapsibleTrigger>
          <CollapsibleContent>
            <CardContent className="space-y-4">
              <div>
                <Label>Quick Add Skills</Label>
                <div className="flex flex-wrap gap-2 mt-2">
                  {commonSkills.filter(skill => !form.watch('skills').includes(skill)).map((skill) => (
                    <Button
                      key={skill}
                      type="button"
                      variant="outline"
                      size="sm"
                      onClick={() => addSkill(skill)}
                    >
                      <Plus className="w-3 h-3 mr-1" />
                      {skill}
                    </Button>
                  ))}
                </div>
              </div>

              <div>
                <Label>Current Skills</Label>
                <div className="flex flex-wrap gap-2 mt-2">
                  {form.watch('skills').map((skill, index) => (
                    <Badge key={index} variant="secondary" className="pl-3 pr-1">
                      {skill}
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => removeSkill(skill)}
                        className="ml-1 h-auto p-0 text-gray-500 hover:text-red-500"
                      >
                        <X className="w-3 h-3" />
                      </Button>
                    </Badge>
                  ))}
                </div>
              </div>
            </CardContent>
          </CollapsibleContent>
        </Card>
      </Collapsible>

      {/* Save Button */}
      <div className="flex justify-end">
        <Button type="submit" disabled={isSaving}>
          {isSaving ? 'Saving...' : 'Save Ministry Roles'}
        </Button>
      </div>
    </form>
  );
};

export default MinistryRolesTab;