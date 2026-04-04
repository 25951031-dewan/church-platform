import React, { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'react-hot-toast';
import { FiPlus, FiX, FiClock } from 'react-icons/fi';

import { Button } from '@ui/button';
import { Input } from '@ui/input';
import { Label } from '@ui/label';
import { Textarea } from '@ui/textarea';
import { Select } from '@ui/select';

interface GeneralTabProps {
  church: any;
  churchId: string;
}

interface ServiceHour {
  day: string;
  time: string;
  service_type: string;
}

const DAYS = [
  { value: 'sunday', label: 'Sunday' },
  { value: 'monday', label: 'Monday' },
  { value: 'tuesday', label: 'Tuesday' },
  { value: 'wednesday', label: 'Wednesday' },
  { value: 'thursday', label: 'Thursday' },
  { value: 'friday', label: 'Friday' },
  { value: 'saturday', label: 'Saturday' },
];

export default function GeneralTab({ church, churchId }: GeneralTabProps) {
  const queryClient = useQueryClient();
  const [formData, setFormData] = useState({
    name: church.name || '',
    email: church.email || '',
    phone: church.phone || '',
    website: church.website || '',
    address: church.address || '',
    city: church.city || '',
    state: church.state || '',
    zip_code: church.zip_code || '',
    country: church.country || '',
    denomination: church.denomination || '',
    short_description: church.short_description || '',
    service_hours: church.service_hours || [],
  });

  const updateMutation = useMutation({
    mutationFn: async (data: any) => {
      const response = await fetch(`/api/churches/${churchId}/website/general`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      });
      if (!response.ok) throw new Error('Failed to update general settings');
      return response.json();
    },
    onSuccess: () => {
      toast.success('General settings updated successfully');
      queryClient.invalidateQueries({ queryKey: ['church-website', churchId] });
    },
    onError: () => {
      toast.error('Failed to update general settings');
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    updateMutation.mutate(formData);
  };

  const handleInputChange = (field: string, value: string) => {
    setFormData(prev => ({ ...prev, [field]: value }));
  };

  const addServiceHour = () => {
    setFormData(prev => ({
      ...prev,
      service_hours: [
        ...prev.service_hours,
        { day: 'sunday', time: '', service_type: '' }
      ]
    }));
  };

  const updateServiceHour = (index: number, field: keyof ServiceHour, value: string) => {
    setFormData(prev => ({
      ...prev,
      service_hours: prev.service_hours.map((hour, i) => 
        i === index ? { ...hour, [field]: value } : hour
      )
    }));
  };

  const removeServiceHour = (index: number) => {
    setFormData(prev => ({
      ...prev,
      service_hours: prev.service_hours.filter((_, i) => i !== index)
    }));
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      {/* Basic Information */}
      <div className="space-y-4">
        <h3 className="text-lg font-medium text-gray-900">Basic Information</h3>
        
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <Label htmlFor="name">Church Name *</Label>
            <Input
              id="name"
              value={formData.name}
              onChange={(e) => handleInputChange('name', e.target.value)}
              required
            />
          </div>
          
          <div>
            <Label htmlFor="denomination">Denomination</Label>
            <Input
              id="denomination"
              value={formData.denomination}
              onChange={(e) => handleInputChange('denomination', e.target.value)}
              placeholder="e.g., Baptist, Methodist, etc."
            />
          </div>
        </div>

        <div>
          <Label htmlFor="short_description">Short Description</Label>
          <Textarea
            id="short_description"
            value={formData.short_description}
            onChange={(e) => handleInputChange('short_description', e.target.value)}
            placeholder="A brief description of your church (used in search results)"
            rows={3}
            maxLength={500}
          />
          <p className="text-sm text-gray-500 mt-1">
            {formData.short_description.length}/500 characters
          </p>
        </div>
      </div>

      {/* Contact Information */}
      <div className="space-y-4">
        <h3 className="text-lg font-medium text-gray-900">Contact Information</h3>
        
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <Label htmlFor="email">Email</Label>
            <Input
              id="email"
              type="email"
              value={formData.email}
              onChange={(e) => handleInputChange('email', e.target.value)}
            />
          </div>
          
          <div>
            <Label htmlFor="phone">Phone</Label>
            <Input
              id="phone"
              type="tel"
              value={formData.phone}
              onChange={(e) => handleInputChange('phone', e.target.value)}
            />
          </div>
          
          <div>
            <Label htmlFor="website">Website</Label>
            <Input
              id="website"
              type="url"
              value={formData.website}
              onChange={(e) => handleInputChange('website', e.target.value)}
              placeholder="https://yourchurch.com"
            />
          </div>
        </div>
      </div>

      {/* Address */}
      <div className="space-y-4">
        <h3 className="text-lg font-medium text-gray-900">Address</h3>
        
        <div>
          <Label htmlFor="address">Street Address</Label>
          <Input
            id="address"
            value={formData.address}
            onChange={(e) => handleInputChange('address', e.target.value)}
          />
        </div>
        
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div className="md:col-span-2">
            <Label htmlFor="city">City</Label>
            <Input
              id="city"
              value={formData.city}
              onChange={(e) => handleInputChange('city', e.target.value)}
            />
          </div>
          
          <div>
            <Label htmlFor="state">State</Label>
            <Input
              id="state"
              value={formData.state}
              onChange={(e) => handleInputChange('state', e.target.value)}
            />
          </div>
          
          <div>
            <Label htmlFor="zip_code">ZIP Code</Label>
            <Input
              id="zip_code"
              value={formData.zip_code}
              onChange={(e) => handleInputChange('zip_code', e.target.value)}
            />
          </div>
        </div>
        
        <div>
          <Label htmlFor="country">Country</Label>
          <Input
            id="country"
            value={formData.country}
            onChange={(e) => handleInputChange('country', e.target.value)}
            placeholder="United States"
          />
        </div>
      </div>

      {/* Service Hours */}
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <h3 className="text-lg font-medium text-gray-900">Service Hours</h3>
          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={addServiceHour}
            icon={<FiPlus />}
          >
            Add Service
          </Button>
        </div>
        
        {formData.service_hours.map((hour, index) => (
          <div key={index} className="flex items-center space-x-3 p-3 border border-gray-200 rounded-lg">
            <FiClock className="text-gray-400 flex-shrink-0" />
            
            <Select
              value={hour.day}
              onValueChange={(value) => updateServiceHour(index, 'day', value)}
              className="w-32"
            >
              {DAYS.map(day => (
                <option key={day.value} value={day.value}>
                  {day.label}
                </option>
              ))}
            </Select>
            
            <Input
              value={hour.time}
              onChange={(e) => updateServiceHour(index, 'time', e.target.value)}
              placeholder="9:00 AM - 10:30 AM"
              className="flex-1"
            />
            
            <Input
              value={hour.service_type}
              onChange={(e) => updateServiceHour(index, 'service_type', e.target.value)}
              placeholder="Sunday Service, Bible Study, etc."
              className="flex-1"
            />
            
            <Button
              type="button"
              variant="ghost"
              size="sm"
              onClick={() => removeServiceHour(index)}
              icon={<FiX />}
              className="text-red-600 hover:text-red-700"
            />
          </div>
        ))}
        
        {formData.service_hours.length === 0 && (
          <div className="text-center py-8 text-gray-500">
            <FiClock className="w-12 h-12 mx-auto mb-3 text-gray-300" />
            <p>No service hours added yet</p>
            <p className="text-sm">Click "Add Service" to get started</p>
          </div>
        )}
      </div>

      {/* Submit Button */}
      <div className="flex justify-end pt-6 border-t border-gray-200">
        <Button
          type="submit"
          loading={updateMutation.isPending}
          className="w-full md:w-auto"
        >
          Save General Settings
        </Button>
      </div>
    </form>
  );
}