import React from 'react';
import VersesManager from './admin/VersesManager';
import BlessingsManager from './admin/BlessingsManager';
import PrayersManager from './admin/PrayersManager';
import EventsManager from './admin/EventsManager';
import PostsManager from './admin/PostsManager';
import SermonsManager from './admin/SermonsManager';
import BooksManager from './admin/BooksManager';
import BibleStudiesManager from './admin/BibleStudiesManager';
import ReviewsManager from './admin/ReviewsManager';
import GalleriesManager from './admin/GalleriesManager';
import MinistriesManager from './admin/MinistriesManager';
import ContactsManager from './admin/ContactsManager';
import NewsletterManager from './admin/NewsletterManager';
import DonationsManager from './admin/DonationsManager';
import UsersManager from './admin/UsersManager';
import SettingsManager from './admin/SettingsManager';
import AnnouncementsManager from './admin/AnnouncementsManager';
import PagesManager from './admin/PagesManager';
import CategoriesManager from './admin/CategoriesManager';
import MenuManager from './admin/MenuManager';
import RolesManager from './admin/RolesManager';
import TestimoniesManager from './admin/TestimoniesManager';
import HomepageCustomizer from './admin/HomepageCustomizer';
import SystemManager from './admin/SystemManager';
import AppearanceManager from './admin/AppearanceManager';
import MobileThemeManager from './admin/MobileThemeManager';
import LocalizationManager from './admin/LocalizationManager';
import ProfileManager from './admin/ProfileManager';
import SitemapManager from './admin/SitemapManager';
import ChurchDirectory from './admin/ChurchDirectory';
import ChurchBuilder from './admin/ChurchBuilder';
import CommunityManager from './admin/CommunityManager';
import CounselingManager from './admin/CounselingManager';

const components = {
    'verses': VersesManager,
    'blessings': BlessingsManager,
    'prayers': PrayersManager,
    'prayer-requests': PrayersManager,
    'events': EventsManager,
    'posts': PostsManager,
    'sermons': SermonsManager,
    'books': BooksManager,
    'bible-studies': BibleStudiesManager,
    'reviews': ReviewsManager,
    'galleries': GalleriesManager,
    'ministries': MinistriesManager,
    'contacts': ContactsManager,
    'newsletter': NewsletterManager,
    'donations': DonationsManager,
    'users': UsersManager,
    'settings': SettingsManager,
    'announcements': AnnouncementsManager,
    'pages': PagesManager,
    'categories': CategoriesManager,
    'menus': MenuManager,
    'roles': RolesManager,
    'testimonies': TestimoniesManager,
    'homepage': HomepageCustomizer,
    'system': SystemManager,
    'appearance': AppearanceManager,
    'mobile-theme': MobileThemeManager,
    'translations': LocalizationManager,
    'profile': ProfileManager,
    'sitemap': SitemapManager,
    'churches': ChurchDirectory,
    'church-builder': ChurchBuilder,
    'community': CommunityManager,
    'counseling': CounselingManager,
};

export default function AdminApp({ section }) {
    const Component = components[section];

    if (!Component) {
        return <div className="p-8 text-center text-gray-500">Section not found: {section}</div>;
    }

    return <Component />;
}
