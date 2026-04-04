import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { 
  FiMapPin, 
  FiPhone, 
  FiMail, 
  FiGlobe, 
  FiSearch, 
  FiFilter, 
  FiStar,
  FiUsers,
  FiEye 
} from 'react-icons/fi';

import { Input } from '@ui/Input';
import { Button } from '@ui/button';
import { Select } from '@ui/Select';
import { Badge } from '@ui/Badge';

interface ChurchDirectoryProps {
  churches: {
    data: any[];
    meta: any;
  };
  filters: {
    search?: string;
    location?: string;
    denomination?: string;
    sort: string;
  };
  denominations: string[];
}

export default function ChurchDirectory({ churches, filters, denominations }: ChurchDirectoryProps) {
  const [searchTerm, setSearchTerm] = useState(filters.search || '');
  const [locationFilter, setLocationFilter] = useState(filters.location || '');
  const [denominationFilter, setDenominationFilter] = useState(filters.denomination || '');
  const [sortBy, setSortBy] = useState(filters.sort || 'featured');

  const handleSearch = () => {
    const params = new URLSearchParams();
    if (searchTerm) params.set('search', searchTerm);
    if (locationFilter) params.set('location', locationFilter);
    if (denominationFilter) params.set('denomination', denominationFilter);
    if (sortBy !== 'featured') params.set('sort', sortBy);

    const queryString = params.toString();
    window.location.href = `/churches${queryString ? `?${queryString}` : ''}`;
  };

  const handleKeyPress = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter') {
      handleSearch();
    }
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white shadow-sm">
        <div className="max-w-7xl mx-auto px-4 py-8">
          <div className="text-center mb-8">
            <h1 className="text-3xl font-bold text-gray-900">Find a Church</h1>
            <p className="text-lg text-gray-600 mt-2">
              Discover vibrant church communities near you
            </p>
          </div>

          {/* Search and Filters */}
          <div className="bg-gray-50 rounded-lg p-6">
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
              <div className="md:col-span-2">
                <Input
                  placeholder="Search by church name, denomination, or description..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  onKeyPress={handleKeyPress}
                  icon={<FiSearch />}
                />
              </div>
              
              <div>
                <Input
                  placeholder="City or State"
                  value={locationFilter}
                  onChange={(e) => setLocationFilter(e.target.value)}
                  onKeyPress={handleKeyPress}
                  icon={<FiMapPin />}
                />
              </div>
              
              <div>
                <Select
                  value={denominationFilter}
                  onValueChange={setDenominationFilter}
                  placeholder="All Denominations"
                >
                  <option value="">All Denominations</option>
                  {denominations.map(denomination => (
                    <option key={denomination} value={denomination}>
                      {denomination}
                    </option>
                  ))}
                </Select>
              </div>
            </div>
            
            <div className="flex items-center justify-between">
              <Select
                value={sortBy}
                onValueChange={setSortBy}
                className="w-48"
              >
                <option value="featured">Featured</option>
                <option value="newest">Newest</option>
                <option value="name">Name A-Z</option>
                <option value="popular">Most Popular</option>
              </Select>
              
              <Button onClick={handleSearch} icon={<FiSearch />}>
                Search
              </Button>
            </div>
          </div>
        </div>
      </div>

      {/* Results */}
      <div className="max-w-7xl mx-auto px-4 py-8">
        {/* Results Header */}
        <div className="flex items-center justify-between mb-6">
          <div>
            <h2 className="text-xl font-semibold text-gray-900">
              {churches.data.length} {churches.data.length === 1 ? 'Church' : 'Churches'} Found
            </h2>
            {filters.search && (
              <p className="text-gray-600">
                Search results for "{filters.search}"
              </p>
            )}
          </div>
          
          {/* Active Filters */}
          <div className="flex items-center space-x-2">
            {filters.location && (
              <Badge variant="secondary" className="flex items-center space-x-1">
                <FiMapPin className="w-3 h-3" />
                <span>{filters.location}</span>
              </Badge>
            )}
            {filters.denomination && (
              <Badge variant="secondary">
                {filters.denomination}
              </Badge>
            )}
          </div>
        </div>

        {/* Church Grid */}
        {churches.data.length > 0 ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {churches.data.map((church: any) => (
              <ChurchCard key={church.id} church={church} />
            ))}
          </div>
        ) : (
          <div className="text-center py-12">
            <div className="w-24 h-24 mx-auto mb-4 bg-gray-200 rounded-full flex items-center justify-center">
              <FiSearch className="w-12 h-12 text-gray-400" />
            </div>
            <h3 className="text-lg font-medium text-gray-900 mb-2">No churches found</h3>
            <p className="text-gray-600 mb-4">Try adjusting your search criteria or filters</p>
            <Button
              variant="outline"
              onClick={() => {
                setSearchTerm('');
                setLocationFilter('');
                setDenominationFilter('');
                setSortBy('featured');
                window.location.href = '/churches';
              }}
            >
              Clear all filters
            </Button>
          </div>
        )}

        {/* Pagination */}
        {churches.meta.total > churches.meta.per_page && (
          <div className="mt-12 flex justify-center">
            <div className="flex items-center space-x-2">
              {/* Add pagination component here */}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

function ChurchCard({ church }: { church: any }) {
  return (
    <Link
      to={`/church/${church.slug}`}
      className="bg-white rounded-lg shadow-sm border hover:shadow-md transition-shadow group"
    >
      <div className="relative">
        {church.cover_photo_url ? (
          <img
            src={church.cover_photo_url}
            alt={church.name}
            className="w-full h-48 object-cover rounded-t-lg"
          />
        ) : (
          <div className="w-full h-48 bg-gradient-to-br from-gray-100 to-gray-200 rounded-t-lg flex items-center justify-center">
            <div className="text-gray-400">
              <FiMapPin className="w-12 h-12 mx-auto mb-2" />
              <p className="text-sm font-medium">No Image</p>
            </div>
          </div>
        )}
        
        <div className="absolute top-3 left-3 flex items-center space-x-2">
          {church.is_featured && (
            <Badge variant="primary" className="flex items-center space-x-1">
              <FiStar className="w-3 h-3" />
              <span>Featured</span>
            </Badge>
          )}
          {church.is_verified && (
            <Badge variant="success">
              Verified
            </Badge>
          )}
        </div>
      </div>
      
      <div className="p-6">
        <div className="flex items-start justify-between mb-3">
          <div className="flex-1">
            <h3 className="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">
              {church.name}
            </h3>
            {church.denomination && (
              <p className="text-sm text-gray-600">{church.denomination}</p>
            )}
          </div>
          
          {church.logo_url && (
            <img
              src={church.logo_url}
              alt={`${church.name} logo`}
              className="w-12 h-12 rounded-lg object-contain border border-gray-200"
            />
          )}
        </div>
        
        {church.short_description && (
          <p className="text-gray-600 text-sm mb-4 line-clamp-2">
            {church.short_description}
          </p>
        )}
        
        <div className="space-y-2">
          {church.city && church.state && (
            <div className="flex items-center text-sm text-gray-600">
              <FiMapPin className="w-4 h-4 mr-2 flex-shrink-0" />
              <span>{church.city}, {church.state}</span>
            </div>
          )}
          
          {church.phone && (
            <div className="flex items-center text-sm text-gray-600">
              <FiPhone className="w-4 h-4 mr-2 flex-shrink-0" />
              <span>{church.phone}</span>
            </div>
          )}
          
          {church.email && (
            <div className="flex items-center text-sm text-gray-600">
              <FiMail className="w-4 h-4 mr-2 flex-shrink-0" />
              <span>{church.email}</span>
            </div>
          )}
        </div>
        
        <div className="flex items-center justify-between mt-4 pt-4 border-t border-gray-100">
          <div className="flex items-center text-sm text-gray-500">
            <FiUsers className="w-4 h-4 mr-1" />
            <span>{church.approved_members_count || 0} members</span>
          </div>
          
          <div className="flex items-center text-sm text-gray-500">
            <FiEye className="w-4 h-4 mr-1" />
            <span>{church.view_count || 0} views</span>
          </div>
        </div>
      </div>
    </Link>
  );
}