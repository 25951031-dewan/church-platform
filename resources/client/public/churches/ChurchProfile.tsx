import React from 'react';
import { Link } from 'react-router-dom';
import { 
  FiMapPin, 
  FiPhone, 
  FiMail, 
  FiGlobe,
  FiClock,
  FiUsers,
  FiCalendar,
  FiFacebook,
  FiTwitter,
  FiInstagram,
  FiYoutube,
  FiDownload,
  FiStar,
  FiShare2,
  FiNavigation
} from 'react-icons/fi';

import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';

interface ChurchProfileProps {
  church: any;
  seo: {
    title: string;
    description: string;
    canonical: string;
  };
}

export default function ChurchProfile({ church, seo }: ChurchProfileProps) {
  const primaryColor = church.primary_color || '#4F46E5';
  const secondaryColor = church.secondary_color;

  const socialLinks = [
    { key: 'facebook_url', icon: FiFacebook, label: 'Facebook', color: '#1877F2' },
    { key: 'instagram_url', icon: FiInstagram, label: 'Instagram', color: '#E4405F' },
    { key: 'youtube_url', icon: FiYoutube, label: 'YouTube', color: '#FF0000' },
    { key: 'twitter_url', icon: FiTwitter, label: 'Twitter', color: '#1DA1F2' },
    { key: 'tiktok_url', icon: FiShare2, label: 'TikTok', color: '#000000' },
  ].filter(social => church[social.key]);

  const handleShare = async () => {
    if (navigator.share) {
      try {
        await navigator.share({
          title: church.name,
          text: church.short_description,
          url: window.location.href,
        });
      } catch (err) {
        // User cancelled sharing
      }
    } else {
      // Fallback: copy to clipboard
      navigator.clipboard.writeText(window.location.href);
      // You might want to show a toast notification here
    }
  };

  const handleDirections = () => {
    if (church.address || (church.city && church.state)) {
      const address = church.address || `${church.city}, ${church.state}`;
      const url = `https://maps.google.com/maps?q=${encodeURIComponent(address)}`;
      window.open(url, '_blank');
    }
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Hero Section */}
      <div className="relative">
        {church.cover_photo_url ? (
          <div 
            className="h-80 bg-cover bg-center"
            style={{ backgroundImage: `url(${church.cover_photo_url})` }}
          >
            <div className="absolute inset-0 bg-black bg-opacity-40" />
          </div>
        ) : (
          <div 
            className="h-80"
            style={{ backgroundColor: primaryColor }}
          >
            <div className="absolute inset-0 bg-black bg-opacity-20" />
          </div>
        )}
        
        <div className="absolute inset-0 flex items-end">
          <div className="max-w-7xl mx-auto px-4 pb-8 w-full">
            <div className="flex items-end space-x-6">
              {church.logo_url && (
                <img
                  src={church.logo_url}
                  alt={`${church.name} logo`}
                  className="w-24 h-24 rounded-lg bg-white shadow-lg border-4 border-white object-contain"
                />
              )}
              
              <div className="flex-1 text-white">
                <div className="flex items-center space-x-3 mb-2">
                  <h1 className="text-4xl font-bold">{church.name}</h1>
                  <div className="flex items-center space-x-2">
                    {church.is_featured && (
                      <Badge className="bg-yellow-500 text-yellow-900">
                        <FiStar className="w-3 h-3 mr-1" />
                        Featured
                      </Badge>
                    )}
                    {church.is_verified && (
                      <Badge className="bg-green-500 text-green-900">
                        Verified
                      </Badge>
                    )}
                  </div>
                </div>
                
                <div className="flex items-center space-x-6 text-lg">
                  {church.denomination && (
                    <span>{church.denomination}</span>
                  )}
                  {church.city && church.state && (
                    <div className="flex items-center">
                      <FiMapPin className="w-5 h-5 mr-1" />
                      <span>{church.city}, {church.state}</span>
                    </div>
                  )}
                  {church.year_founded && (
                    <div className="flex items-center">
                      <FiCalendar className="w-5 h-5 mr-1" />
                      <span>Est. {church.year_founded}</span>
                    </div>
                  )}
                </div>
              </div>
              
              <div className="flex items-center space-x-3">
                <Button
                  variant="outline"
                  onClick={handleShare}
                  icon={<FiShare2 />}
                  className="text-white border-white hover:bg-white hover:text-gray-900"
                >
                  Share
                </Button>
                {(church.address || (church.city && church.state)) && (
                  <Button
                    onClick={handleDirections}
                    icon={<FiNavigation />}
                    className="bg-white text-gray-900 hover:bg-gray-100"
                  >
                    Get Directions
                  </Button>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-7xl mx-auto px-4 py-8">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Main Content */}
          <div className="lg:col-span-2 space-y-8">
            {/* Description */}
            {church.short_description && (
              <div className="bg-white rounded-lg shadow-sm p-6">
                <p className="text-lg text-gray-700 leading-relaxed">
                  {church.short_description}
                </p>
              </div>
            )}

            {/* About */}
            {church.description && (
              <div className="bg-white rounded-lg shadow-sm p-6">
                <h2 className="text-2xl font-bold text-gray-900 mb-4" style={{ color: primaryColor }}>
                  About Our Church
                </h2>
                <div 
                  className="prose prose-lg max-w-none"
                  dangerouslySetInnerHTML={{ __html: church.description }}
                />
              </div>
            )}

            {/* History */}
            {church.history && (
              <div className="bg-white rounded-lg shadow-sm p-6">
                <h2 className="text-2xl font-bold text-gray-900 mb-4" style={{ color: primaryColor }}>
                  Our History
                </h2>
                <div 
                  className="prose prose-lg max-w-none"
                  dangerouslySetInnerHTML={{ __html: church.history }}
                />
              </div>
            )}

            {/* Mission & Vision */}
            {(church.mission_statement || church.vision_statement) && (
              <div className="bg-white rounded-lg shadow-sm p-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  {church.mission_statement && (
                    <div>
                      <h3 className="text-xl font-semibold text-gray-900 mb-3" style={{ color: primaryColor }}>
                        Our Mission
                      </h3>
                      <p className="text-gray-700">{church.mission_statement}</p>
                    </div>
                  )}
                  {church.vision_statement && (
                    <div>
                      <h3 className="text-xl font-semibold text-gray-900 mb-3" style={{ color: primaryColor }}>
                        Our Vision
                      </h3>
                      <p className="text-gray-700">{church.vision_statement}</p>
                    </div>
                  )}
                </div>
              </div>
            )}

            {/* Documents */}
            {church.documents && church.documents.length > 0 && (
              <div className="bg-white rounded-lg shadow-sm p-6">
                <h2 className="text-2xl font-bold text-gray-900 mb-4" style={{ color: primaryColor }}>
                  Resources & Documents
                </h2>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {church.documents.map((doc: any, index: number) => (
                    <a
                      key={index}
                      href={doc.url}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="flex items-center p-4 border border-gray-200 rounded-lg hover:border-gray-300 transition-colors"
                    >
                      <FiDownload className="w-6 h-6 text-gray-400 mr-3 flex-shrink-0" />
                      <div>
                        <p className="font-medium text-gray-900">{doc.title}</p>
                        {doc.size && (
                          <p className="text-sm text-gray-500">
                            {Math.round(doc.size / 1024)} KB
                          </p>
                        )}
                      </div>
                    </a>
                  ))}
                </div>
              </div>
            )}

            {/* Custom Pages */}
            {church.published_pages && church.published_pages.length > 0 && (
              <div className="bg-white rounded-lg shadow-sm p-6">
                <h2 className="text-2xl font-bold text-gray-900 mb-4" style={{ color: primaryColor }}>
                  More Information
                </h2>
                <div className="space-y-2">
                  {church.published_pages.map((page: any) => (
                    <Link
                      key={page.id}
                      to={`/church/${church.slug}/${page.slug}`}
                      className="block p-3 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                      <h3 className="font-medium text-gray-900">{page.title}</h3>
                    </Link>
                  ))}
                </div>
              </div>
            )}
          </div>

          {/* Sidebar */}
          <div className="space-y-6">
            {/* Contact Info */}
            <div className="bg-white rounded-lg shadow-sm p-6">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Contact Information</h3>
              <div className="space-y-3">
                {church.address && (
                  <div className="flex items-start">
                    <FiMapPin className="w-5 h-5 text-gray-400 mr-3 mt-0.5 flex-shrink-0" />
                    <div>
                      <p className="text-gray-900">{church.address}</p>
                      {church.city && church.state && (
                        <p className="text-gray-600">
                          {church.city}, {church.state} {church.zip_code}
                        </p>
                      )}
                      {church.country && church.country !== 'United States' && (
                        <p className="text-gray-600">{church.country}</p>
                      )}
                    </div>
                  </div>
                )}
                
                {church.phone && (
                  <div className="flex items-center">
                    <FiPhone className="w-5 h-5 text-gray-400 mr-3 flex-shrink-0" />
                    <a 
                      href={`tel:${church.phone}`}
                      className="text-gray-900 hover:underline"
                    >
                      {church.phone}
                    </a>
                  </div>
                )}
                
                {church.email && (
                  <div className="flex items-center">
                    <FiMail className="w-5 h-5 text-gray-400 mr-3 flex-shrink-0" />
                    <a 
                      href={`mailto:${church.email}`}
                      className="text-gray-900 hover:underline"
                    >
                      {church.email}
                    </a>
                  </div>
                )}
                
                {church.website && (
                  <div className="flex items-center">
                    <FiGlobe className="w-5 h-5 text-gray-400 mr-3 flex-shrink-0" />
                    <a 
                      href={church.website}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="text-gray-900 hover:underline"
                    >
                      Visit Website
                    </a>
                  </div>
                )}
              </div>
            </div>

            {/* Service Hours */}
            {church.service_hours && church.service_hours.length > 0 && (
              <div className="bg-white rounded-lg shadow-sm p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Service Times</h3>
                <div className="space-y-3">
                  {church.service_hours.map((service: any, index: number) => (
                    <div key={index} className="flex items-start">
                      <FiClock className="w-5 h-5 text-gray-400 mr-3 mt-0.5 flex-shrink-0" />
                      <div>
                        <p className="font-medium text-gray-900">
                          {service.day.charAt(0).toUpperCase() + service.day.slice(1)}s
                        </p>
                        <p className="text-gray-600">{service.time}</p>
                        {service.service_type && (
                          <p className="text-sm text-gray-500">{service.service_type}</p>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Social Media */}
            {socialLinks.length > 0 && (
              <div className="bg-white rounded-lg shadow-sm p-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">Connect With Us</h3>
                <div className="space-y-3">
                  {socialLinks.map(social => {
                    const IconComponent = social.icon;
                    return (
                      <a
                        key={social.key}
                        href={church[social.key]}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex items-center p-3 rounded-lg border border-gray-200 hover:border-gray-300 transition-colors"
                      >
                        <IconComponent 
                          className="w-5 h-5 mr-3 flex-shrink-0" 
                          style={{ color: social.color }}
                        />
                        <span className="text-gray-900">{social.label}</span>
                      </a>
                    );
                  })}
                </div>
              </div>
            )}

            {/* Stats */}
            <div className="bg-white rounded-lg shadow-sm p-6">
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Community</h3>
              <div className="space-y-3">
                <div className="flex items-center justify-between">
                  <div className="flex items-center">
                    <FiUsers className="w-5 h-5 text-gray-400 mr-3" />
                    <span className="text-gray-600">Members</span>
                  </div>
                  <span className="font-medium text-gray-900">
                    {church.approved_members_count || 0}
                  </span>
                </div>
                <div className="flex items-center justify-between">
                  <div className="flex items-center">
                    <FiCalendar className="w-5 h-5 text-gray-400 mr-3" />
                    <span className="text-gray-600">Profile Views</span>
                  </div>
                  <span className="font-medium text-gray-900">
                    {church.view_count || 0}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}