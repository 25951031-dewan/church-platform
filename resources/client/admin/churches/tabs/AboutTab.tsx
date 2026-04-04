import React, { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'react-hot-toast';
import { FiFileText, FiUpload, FiDownload, FiX, FiCalendar } from 'react-icons/fi';

import { Button } from '@/components/ui/Button';
import { Label } from '@/components/ui/Label';
import { Textarea } from '@/components/ui/Textarea';
import { Input } from '@/components/ui/Input';

interface AboutTabProps {
  church: any;
  churchId: string;
}

export default function AboutTab({ church, churchId }: AboutTabProps) {
  const queryClient = useQueryClient();
  const [formData, setFormData] = useState({
    description: church.description || '',
    history: church.history || '',
    mission_statement: church.mission_statement || '',
    vision_statement: church.vision_statement || '',
    year_founded: church.year_founded || '',
  });

  const [uploadingDoc, setUploadingDoc] = useState(false);
  const [docTitle, setDocTitle] = useState('');

  const updateMutation = useMutation({
    mutationFn: async (data: any) => {
      const response = await fetch(`/api/churches/${churchId}/website/about`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      });
      if (!response.ok) throw new Error('Failed to update about section');
      return response.json();
    },
    onSuccess: () => {
      toast.success('About section updated successfully');
      queryClient.invalidateQueries(['church-website', churchId]);
    },
    onError: () => {
      toast.error('Failed to update about section');
    },
  });

  const uploadDocMutation = useMutation({
    mutationFn: async ({ file, title }: { file: File; title: string }) => {
      const formData = new FormData();
      formData.append('document', file);
      formData.append('title', title);

      const response = await fetch(`/api/churches/${churchId}/website/documents`, {
        method: 'POST',
        body: formData,
      });
      if (!response.ok) throw new Error('Failed to upload document');
      return response.json();
    },
    onSuccess: () => {
      toast.success('Document uploaded successfully');
      queryClient.invalidateQueries(['church-website', churchId]);
      setDocTitle('');
    },
    onError: () => {
      toast.error('Failed to upload document');
    },
    onSettled: () => {
      setUploadingDoc(false);
    },
  });

  const deleteDocMutation = useMutation({
    mutationFn: async (index: number) => {
      const response = await fetch(`/api/churches/${churchId}/website/documents/${index}`, {
        method: 'DELETE',
      });
      if (!response.ok) throw new Error('Failed to delete document');
      return response.json();
    },
    onSuccess: () => {
      toast.success('Document deleted successfully');
      queryClient.invalidateQueries(['church-website', churchId]);
    },
    onError: () => {
      toast.error('Failed to delete document');
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    updateMutation.mutate(formData);
  };

  const handleInputChange = (field: string, value: string) => {
    setFormData(prev => ({ ...prev, [field]: value }));
  };

  const handleDocumentUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file || !docTitle.trim()) {
      toast.error('Please provide both a file and title');
      return;
    }

    if (file.size > 10 * 1024 * 1024) { // 10MB limit
      toast.error('File must be smaller than 10MB');
      return;
    }

    const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    if (!allowedTypes.includes(file.type)) {
      toast.error('Only PDF and Word documents are allowed');
      return;
    }

    setUploadingDoc(true);
    uploadDocMutation.mutate({ file, title: docTitle.trim() });
  };

  const formatFileSize = (bytes: number) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + ' KB';
    return Math.round(bytes / (1024 * 1024)) + ' MB';
  };

  return (
    <div className="space-y-8">
      {/* Text Content Form */}
      <form onSubmit={handleSubmit} className="space-y-6">
        <div className="space-y-4">
          <h3 className="text-lg font-medium text-gray-900">Church Information</h3>
          
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="md:col-span-2">
              <Label htmlFor="description">About Our Church</Label>
              <Textarea
                id="description"
                value={formData.description}
                onChange={(e) => handleInputChange('description', e.target.value)}
                placeholder="Tell visitors about your church, what you believe, and what makes your community special..."
                rows={6}
                maxLength={5000}
              />
              <p className="text-sm text-gray-500 mt-1">
                {formData.description.length}/5000 characters
              </p>
            </div>

            <div className="md:col-span-2">
              <Label htmlFor="history">Church History</Label>
              <Textarea
                id="history"
                value={formData.history}
                onChange={(e) => handleInputChange('history', e.target.value)}
                placeholder="Share your church's history, founding story, and significant milestones..."
                rows={8}
                maxLength={10000}
              />
              <p className="text-sm text-gray-500 mt-1">
                {formData.history.length}/10000 characters
              </p>
            </div>

            <div>
              <Label htmlFor="mission_statement">Mission Statement</Label>
              <Textarea
                id="mission_statement"
                value={formData.mission_statement}
                onChange={(e) => handleInputChange('mission_statement', e.target.value)}
                placeholder="What is your church's mission?"
                rows={4}
                maxLength={1000}
              />
              <p className="text-sm text-gray-500 mt-1">
                {formData.mission_statement.length}/1000 characters
              </p>
            </div>

            <div>
              <Label htmlFor="vision_statement">Vision Statement</Label>
              <Textarea
                id="vision_statement"
                value={formData.vision_statement}
                onChange={(e) => handleInputChange('vision_statement', e.target.value)}
                placeholder="What is your church's vision for the future?"
                rows={4}
                maxLength={1000}
              />
              <p className="text-sm text-gray-500 mt-1">
                {formData.vision_statement.length}/1000 characters
              </p>
            </div>

            <div className="md:col-span-2">
              <Label htmlFor="year_founded">Year Founded</Label>
              <Input
                id="year_founded"
                type="number"
                value={formData.year_founded}
                onChange={(e) => handleInputChange('year_founded', e.target.value)}
                min="1000"
                max={new Date().getFullYear()}
                className="w-32"
                placeholder="1985"
              />
            </div>
          </div>
        </div>

        <div className="flex justify-end pt-6 border-t border-gray-200">
          <Button
            type="submit"
            loading={updateMutation.isPending}
            className="w-full md:w-auto"
          >
            Save About Information
          </Button>
        </div>
      </form>

      {/* Document Management */}
      <div className="space-y-6 pt-8 border-t border-gray-200">
        <div className="flex items-center justify-between">
          <h3 className="text-lg font-medium text-gray-900">Church Documents</h3>
          <p className="text-sm text-gray-600">Upload bulletins, welcome packets, and other resources</p>
        </div>

        {/* Upload Section */}
        <div className="bg-gray-50 rounded-lg p-4 space-y-4">
          <div>
            <Label htmlFor="doc_title">Document Title</Label>
            <Input
              id="doc_title"
              value={docTitle}
              onChange={(e) => setDocTitle(e.target.value)}
              placeholder="e.g., Weekly Bulletin, Welcome Packet"
              maxLength={100}
            />
          </div>

          <div>
            <Label htmlFor="doc_upload">Upload Document</Label>
            <div className="flex items-center space-x-3">
              <Input
                id="doc_upload"
                type="file"
                accept=".pdf,.doc,.docx"
                onChange={handleDocumentUpload}
                disabled={uploadingDoc || !docTitle.trim()}
                className="flex-1"
              />
              {uploadingDoc && (
                <div className="flex items-center text-sm text-gray-600">
                  <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-primary mr-2" />
                  Uploading...
                </div>
              )}
            </div>
            <p className="text-sm text-gray-500 mt-1">
              Supported formats: PDF, DOC, DOCX (Max 10MB)
            </p>
          </div>
        </div>

        {/* Document List */}
        <div className="space-y-3">
          {church.documents && church.documents.length > 0 ? (
            church.documents.map((doc: any, index: number) => (
              <div key={index} className="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                <div className="flex items-center space-x-3">
                  <FiFileText className="text-gray-400" />
                  <div>
                    <p className="font-medium text-gray-900">{doc.title}</p>
                    <div className="flex items-center space-x-4 text-sm text-gray-500">
                      <span>{doc.original_name}</span>
                      {doc.size && <span>{formatFileSize(doc.size)}</span>}
                      {doc.uploaded_at && (
                        <span className="flex items-center space-x-1">
                          <FiCalendar className="w-3 h-3" />
                          <span>{new Date(doc.uploaded_at).toLocaleDateString()}</span>
                        </span>
                      )}
                    </div>
                  </div>
                </div>
                
                <div className="flex items-center space-x-2">
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => window.open(doc.url, '_blank')}
                    icon={<FiDownload />}
                  >
                    Download
                  </Button>
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => deleteDocMutation.mutate(index)}
                    loading={deleteDocMutation.isPending}
                    icon={<FiX />}
                    className="text-red-600 hover:text-red-700"
                  >
                    Delete
                  </Button>
                </div>
              </div>
            ))
          ) : (
            <div className="text-center py-8 text-gray-500">
              <FiFileText className="w-12 h-12 mx-auto mb-3 text-gray-300" />
              <p>No documents uploaded yet</p>
              <p className="text-sm">Upload your first document to get started</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}