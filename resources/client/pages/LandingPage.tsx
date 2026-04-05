import { useQuery } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { Link } from 'react-router';
import { ArrowRight, Play, Check, Star, Users, Shield, ArrowUp } from 'lucide-react';
import { useState } from 'react';

interface LandingPageSection {
  id: number;
  name: string;
  sort_order: number;
  is_visible: boolean;
  config: Record<string, any>;
}

function HeroSimpleCentered({ config }: { config: any }) {
  const {
    title = 'Welcome to Our Church',
    subtitle = 'Join our community of faith and fellowship',
    description = 'Experience authentic worship, meaningful connections, and spiritual growth in a welcoming environment.',
    primary_cta_text = 'Join Us Today',
    primary_cta_url = '/feed',
    secondary_cta_text = 'Learn More',
    secondary_cta_url = '/about',
    background_image,
    show_search = false,
  } = config;

  return (
    <section className="relative py-20 lg:py-32 overflow-hidden">
      {background_image && (
        <div 
          className="absolute inset-0 bg-cover bg-center"
          style={{ backgroundImage: `url(${background_image})` }}
        />
      )}
      <div className="absolute inset-0 bg-black/40" />
      
      <div className="relative max-w-4xl mx-auto px-4 text-center">
        <h1 className="text-4xl lg:text-6xl font-bold text-white mb-6 leading-tight">
          {title}
        </h1>
        
        {subtitle && (
          <p className="text-xl lg:text-2xl text-gray-300 mb-4">
            {subtitle}
          </p>
        )}
        
        {description && (
          <p className="text-lg text-gray-400 mb-8 max-w-2xl mx-auto">
            {description}
          </p>
        )}
        
        <div className="flex flex-col sm:flex-row gap-4 justify-center items-center">
          <Link
            to={primary_cta_url}
            className="px-8 py-4 bg-indigo-600 text-white text-lg font-semibold rounded-xl hover:bg-indigo-700 transition-colors inline-flex items-center gap-2"
          >
            {primary_cta_text}
            <ArrowRight size={20} />
          </Link>
          
          {secondary_cta_text && (
            <Link
              to={secondary_cta_url}
              className="px-8 py-4 bg-white/10 backdrop-blur-sm border border-white/20 text-white text-lg font-semibold rounded-xl hover:bg-white/20 transition-colors"
            >
              {secondary_cta_text}
            </Link>
          )}
        </div>
        
        {show_search && (
          <div className="mt-8">
            <div className="max-w-md mx-auto relative">
              <input
                type="text"
                placeholder="Search sermons, events..."
                className="w-full px-4 py-3 bg-white/90 backdrop-blur-sm rounded-xl text-gray-900 placeholder-gray-600 border border-white/20"
              />
              <button className="absolute right-3 top-3 text-gray-600 hover:text-gray-900">
                <ArrowRight size={20} />
              </button>
            </div>
          </div>
        )}
      </div>
    </section>
  );
}

function HeroSplitWithScreenshot({ config }: { config: any }) {
  const {
    title = 'Build Your Faith Community',
    subtitle = 'Connect, Grow, Serve',
    description = 'Join thousands of believers in meaningful worship, authentic fellowship, and transformative service.',
    primary_cta_text = 'Get Started',
    primary_cta_url = '/register',
    secondary_cta_text = 'Watch Demo',
    secondary_cta_url = '#',
    screenshot_image,
  } = config;

  return (
    <section className="py-16 lg:py-24">
      <div className="max-w-7xl mx-auto px-4">
        <div className="grid lg:grid-cols-2 gap-12 items-center">
          <div>
            <h1 className="text-4xl lg:text-5xl font-bold text-white mb-6 leading-tight">
              {title}
            </h1>
            
            {subtitle && (
              <p className="text-xl text-indigo-400 mb-4">
                {subtitle}
              </p>
            )}
            
            <p className="text-lg text-gray-400 mb-8">
              {description}
            </p>
            
            <div className="flex flex-col sm:flex-row gap-4">
              <Link
                to={primary_cta_url}
                className="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition-colors inline-flex items-center gap-2"
              >
                {primary_cta_text}
                <ArrowRight size={18} />
              </Link>
              
              {secondary_cta_text && (
                <button className="px-6 py-3 border border-white/20 text-white font-semibold rounded-lg hover:border-white/40 transition-colors inline-flex items-center gap-2">
                  <Play size={16} />
                  {secondary_cta_text}
                </button>
              )}
            </div>
          </div>
          
          <div className="relative">
            {screenshot_image ? (
              <img
                src={screenshot_image}
                alt="Church platform preview"
                className="w-full rounded-xl border border-white/10 shadow-2xl"
              />
            ) : (
              <div className="w-full h-80 bg-[#161920] border border-white/10 rounded-xl flex items-center justify-center">
                <p className="text-gray-500">Screenshot placeholder</p>
              </div>
            )}
          </div>
        </div>
      </div>
    </section>
  );
}

function FeaturesGrid({ config }: { config: any }) {
  const {
    badge = 'Features',
    title = 'Everything you need for church community',
    description = 'Powerful tools to connect, engage, and grow your church family.',
    features = [
      {
        icon: 'Users',
        title: 'Community Building',
        description: 'Foster meaningful connections with member profiles, groups, and messaging.',
      },
      {
        icon: 'Shield',
        title: 'Prayer Ministry',
        description: 'Coordinate prayer requests and build a supportive prayer community.',
      },
      {
        icon: 'Star',
        title: 'Content Library',
        description: 'Share sermons, devotionals, and resources with your congregation.',
      },
    ],
  } = config;

  const iconMap: Record<string, any> = {
    Users,
    Shield,
    Star,
    Check,
    ArrowUp,
  };

  return (
    <section className="py-16 lg:py-24">
      <div className="max-w-7xl mx-auto px-4">
        <div className="text-center mb-16">
          {badge && (
            <span className="px-3 py-1 bg-indigo-500/20 text-indigo-400 text-sm font-semibold rounded-full">
              {badge}
            </span>
          )}
          <h2 className="text-3xl lg:text-4xl font-bold text-white mt-4 mb-4">
            {title}
          </h2>
          <p className="text-lg text-gray-400 max-w-2xl mx-auto">
            {description}
          </p>
        </div>
        
        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
          {features.map((feature: any, index: number) => {
            const IconComponent = iconMap[feature.icon] || Users;
            return (
              <div key={index} className="bg-[#161920] border border-white/5 rounded-xl p-6">
                <div className="w-12 h-12 bg-indigo-600/20 rounded-lg flex items-center justify-center mb-4">
                  <IconComponent size={24} className="text-indigo-400" />
                </div>
                <h3 className="text-lg font-semibold text-white mb-2">
                  {feature.title}
                </h3>
                <p className="text-gray-400">
                  {feature.description}
                </p>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}

function FeatureWithScreenshot({ config }: { config: any }) {
  const {
    badge = 'Experience',
    title = 'Modern tools for timeless ministry',
    description = 'Seamlessly blend traditional values with contemporary technology.',
    screenshot_image,
    features = [
      'Live streaming and virtual events',
      'Mobile-first design for all ages',
      'Secure donation processing',
      'Automated communication tools',
    ],
  } = config;

  return (
    <section className="py-16 lg:py-24">
      <div className="max-w-7xl mx-auto px-4">
        <div className="grid lg:grid-cols-2 gap-12 items-center">
          <div className="relative order-2 lg:order-1">
            {screenshot_image ? (
              <img
                src={screenshot_image}
                alt="Feature preview"
                className="w-full rounded-xl border border-white/10 shadow-2xl"
              />
            ) : (
              <div className="w-full h-80 bg-[#161920] border border-white/10 rounded-xl flex items-center justify-center">
                <p className="text-gray-500">Feature screenshot</p>
              </div>
            )}
          </div>
          
          <div className="order-1 lg:order-2">
            {badge && (
              <span className="px-3 py-1 bg-indigo-500/20 text-indigo-400 text-sm font-semibold rounded-full">
                {badge}
              </span>
            )}
            <h2 className="text-3xl lg:text-4xl font-bold text-white mt-4 mb-4">
              {title}
            </h2>
            <p className="text-lg text-gray-400 mb-8">
              {description}
            </p>
            
            <ul className="space-y-3">
              {features.map((feature: string, index: number) => (
                <li key={index} className="flex items-center gap-3 text-gray-300">
                  <Check size={16} className="text-indigo-400 flex-shrink-0" />
                  {feature}
                </li>
              ))}
            </ul>
          </div>
        </div>
      </div>
    </section>
  );
}

function CtaSimpleCentered({ config }: { config: any }) {
  const {
    title = 'Ready to join our community?',
    description = 'Take the next step in your spiritual journey with us.',
    cta_text = 'Get Started Today',
    cta_url = '/register',
  } = config;

  return (
    <section className="py-16 lg:py-24">
      <div className="max-w-4xl mx-auto px-4 text-center">
        <h2 className="text-3xl lg:text-4xl font-bold text-white mb-4">
          {title}
        </h2>
        <p className="text-lg text-gray-400 mb-8 max-w-2xl mx-auto">
          {description}
        </p>
        <Link
          to={cta_url}
          className="px-8 py-4 bg-indigo-600 text-white text-lg font-semibold rounded-xl hover:bg-indigo-700 transition-colors inline-flex items-center gap-2"
        >
          {cta_text}
          <ArrowRight size={20} />
        </Link>
      </div>
    </section>
  );
}

function LandingPageFooter({ config }: { config: any }) {
  const {
    copyright = `© ${new Date().getFullYear()} Church Community Platform. All rights reserved.`,
    links = [
      { label: 'About', url: '/about' },
      { label: 'Contact', url: '/contact' },
      { label: 'Privacy', url: '/privacy' },
      { label: 'Terms', url: '/terms' },
    ],
    social_links = [],
  } = config;

  return (
    <footer className="border-t border-white/5 py-12">
      <div className="max-w-7xl mx-auto px-4">
        <div className="flex flex-col md:flex-row justify-between items-center gap-6">
          <p className="text-gray-400 text-sm">
            {copyright}
          </p>
          
          <div className="flex items-center gap-6">
            {links.map((link: any, index: number) => (
              <Link
                key={index}
                to={link.url}
                className="text-gray-400 hover:text-white text-sm transition-colors"
              >
                {link.label}
              </Link>
            ))}
          </div>
          
          {social_links.length > 0 && (
            <div className="flex items-center gap-4">
              {social_links.map((social: any, index: number) => (
                <a
                  key={index}
                  href={social.url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-gray-400 hover:text-white transition-colors"
                >
                  {social.label}
                </a>
              ))}
            </div>
          )}
        </div>
      </div>
    </footer>
  );
}

function SectionRenderer({ section }: { section: LandingPageSection }) {
  const { name, config, is_visible } = section;

  if (!is_visible) return null;

  switch (name) {
    case 'hero-simple-centered':
      return <HeroSimpleCentered config={config} />;
    case 'hero-split-with-screenshot':
      return <HeroSplitWithScreenshot config={config} />;
    case 'features-grid':
      return <FeaturesGrid config={config} />;
    case 'feature-with-screenshot':
      return <FeatureWithScreenshot config={config} />;
    case 'cta-simple-centered':
      return <CtaSimpleCentered config={config} />;
    case 'footer':
      return <LandingPageFooter config={config} />;
    default:
      return null;
  }
}

export function LandingPage() {
  const { data: sections = [], isLoading, error } = useQuery({
    queryKey: ['landing-page-sections'],
    queryFn: () => apiClient.get<{ sections: LandingPageSection[] }>('landing-page').then(r => r.data.sections),
  });

  if (isLoading) {
    return (
      <div className="min-h-screen bg-[#0C0E12] flex items-center justify-center">
        <div className="text-gray-400">Loading...</div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen bg-[#0C0E12] flex items-center justify-center">
        <div className="text-center">
          <h1 className="text-2xl font-bold text-white mb-4">Welcome</h1>
          <p className="text-gray-400">Unable to load landing page content.</p>
          <Link
            to="/feed"
            className="mt-4 inline-block px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition-colors"
          >
            Continue to Feed
          </Link>
        </div>
      </div>
    );
  }

  if (sections.length === 0) {
    return (
      <div className="min-h-screen bg-[#0C0E12]">
        <section className="py-20 lg:py-32">
          <div className="max-w-4xl mx-auto px-4 text-center">
            <h1 className="text-4xl lg:text-6xl font-bold text-white mb-6">
              Welcome to Our Church
            </h1>
            <p className="text-xl text-gray-300 mb-8">
              Join our community of faith and fellowship
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <Link
                to="/feed"
                className="px-8 py-4 bg-indigo-600 text-white text-lg font-semibold rounded-xl hover:bg-indigo-700 transition-colors inline-flex items-center gap-2"
              >
                Join Community
                <ArrowRight size={20} />
              </Link>
              <Link
                to="/sermons"
                className="px-8 py-4 bg-white/10 backdrop-blur-sm border border-white/20 text-white text-lg font-semibold rounded-xl hover:bg-white/20 transition-colors"
              >
                Browse Sermons
              </Link>
            </div>
          </div>
        </section>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[#0C0E12]">
      {sections.map(section => (
        <SectionRenderer key={section.id} section={section} />
      ))}
    </div>
  );
}