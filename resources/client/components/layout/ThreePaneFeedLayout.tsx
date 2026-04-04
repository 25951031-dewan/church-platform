// Three-Pane Feed Layout System
// Based on Facebook/Sngine/BeMusic patterns

import React, { useState, useEffect } from 'react';
import { Search, Menu, X, ChevronLeft, ChevronRight, Settings } from 'lucide-react';
import { useMediaQuery } from '@/hooks/useMediaQuery';
import { cn } from '@/lib/utils';

interface ThreePaneFeedLayoutProps {
  leftSidebar: React.ReactNode;
  centerFeed: React.ReactNode;
  rightSidebar: React.ReactNode;
  searchComponent?: React.ReactNode;
}

const ThreePaneFeedLayout: React.FC<ThreePaneFeedLayoutProps> = ({
  leftSidebar,
  centerFeed,
  rightSidebar,
  searchComponent
}) => {
  const [leftSidebarOpen, setLeftSidebarOpen] = useState(false);
  const [rightSidebarOpen, setRightSidebarOpen] = useState(false);
  const [searchOpen, setSearchOpen] = useState(false);
  
  // Responsive breakpoints
  const isMobile = useMediaQuery('(max-width: 768px)');
  const isTablet = useMediaQuery('(max-width: 1024px)');
  const isDesktop = useMediaQuery('(min-width: 1025px)');

  // Auto-open sidebars on desktop
  useEffect(() => {
    if (isDesktop) {
      setLeftSidebarOpen(true);
      setRightSidebarOpen(true);
      setSearchOpen(false);
    } else {
      setLeftSidebarOpen(false);
      setRightSidebarOpen(false);
    }
  }, [isDesktop]);

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-[#0C0E12]">
      {/* Mobile Header */}
      {(isMobile || isTablet) && (
        <MobileHeader
          onMenuToggle={() => setLeftSidebarOpen(!leftSidebarOpen)}
          onSearchToggle={() => setSearchOpen(!searchOpen)}
          searchOpen={searchOpen}
          searchComponent={searchComponent}
        />
      )}

      <div className="flex relative">
        {/* Left Sidebar */}
        <LeftSidebar
          isOpen={leftSidebarOpen}
          onClose={() => setLeftSidebarOpen(false)}
          isMobile={isMobile}
          isTablet={isTablet}
        >
          {leftSidebar}
        </LeftSidebar>

        {/* Center Feed */}
        <main className={cn(
          "flex-1 transition-all duration-300",
          // Desktop spacing
          isDesktop && "mx-4",
          // Mobile/tablet full width when sidebars closed
          (isMobile || isTablet) && !leftSidebarOpen && !rightSidebarOpen && "mx-0",
          // Top padding for mobile header
          (isMobile || isTablet) && "pt-16"
        )}>
          <CenterFeedContainer>
            {centerFeed}
          </CenterFeedContainer>
        </main>

        {/* Right Sidebar */}
        <RightSidebar
          isOpen={rightSidebarOpen}
          onClose={() => setRightSidebarOpen(false)}
          isMobile={isMobile}
          isTablet={isTablet}
        >
          {rightSidebar}
        </RightSidebar>
      </div>

      {/* Mobile/Tablet Sidebar Toggle Buttons */}
      {!isDesktop && (
        <SidebarToggleButtons
          leftOpen={leftSidebarOpen}
          rightOpen={rightSidebarOpen}
          onLeftToggle={() => setLeftSidebarOpen(!leftSidebarOpen)}
          onRightToggle={() => setRightSidebarOpen(!rightSidebarOpen)}
        />
      )}

      {/* Overlay for mobile when sidebars are open */}
      {(isMobile || isTablet) && (leftSidebarOpen || rightSidebarOpen) && (
        <div
          className="fixed inset-0 bg-black bg-opacity-50 z-40"
          onClick={() => {
            setLeftSidebarOpen(false);
            setRightSidebarOpen(false);
          }}
        />
      )}
    </div>
  );
};

// Mobile Header Component
const MobileHeader: React.FC<{
  onMenuToggle: () => void;
  onSearchToggle: () => void;
  searchOpen: boolean;
  searchComponent?: React.ReactNode;
}> = ({ onMenuToggle, onSearchToggle, searchOpen, searchComponent }) => {
  return (
    <>
      <header className="fixed top-0 left-0 right-0 h-16 bg-white dark:bg-[#161920] border-b border-gray-200 dark:border-white/5 z-50">
        <div className="flex items-center justify-between h-full px-4">
          {/* Left: Menu Button */}
          <button
            onClick={onMenuToggle}
            className="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-[#1e2229] transition-colors"
          >
            <Menu className="w-6 h-6" />
          </button>

          {/* Center: Logo/Title */}
          <div className="flex-1 flex justify-center">
            <h1 className="text-lg font-semibold text-gray-900 dark:text-white">
              Church Feed
            </h1>
          </div>

          {/* Right: Search Button */}
          <button
            onClick={onSearchToggle}
            className="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-[#1e2229] transition-colors"
          >
            <Search className="w-6 h-6" />
          </button>
        </div>
      </header>

      {/* Search Overlay */}
      {searchOpen && (
        <div className="fixed top-16 left-0 right-0 bg-white dark:bg-[#161920] border-b border-gray-200 dark:border-white/5 z-40 p-4">
          <div className="flex items-center gap-3">
            <div className="flex-1">
              {searchComponent || <DefaultSearchBar />}
            </div>
            <button
              onClick={onSearchToggle}
              className="p-2 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
            >
              <X className="w-5 h-5" />
            </button>
          </div>
        </div>
      )}
    </>
  );
};

// Left Sidebar Component
const LeftSidebar: React.FC<{
  children: React.ReactNode;
  isOpen: boolean;
  onClose: () => void;
  isMobile: boolean;
  isTablet: boolean;
}> = ({ children, isOpen, onClose, isMobile, isTablet }) => {
  return (
    <aside className={cn(
      "bg-white dark:bg-[#161920] border-r border-gray-200 dark:border-white/5 transition-all duration-300",
      // Desktop: always visible, fixed width
      !isMobile && !isTablet && "w-80 sticky top-0 h-screen overflow-y-auto",
      // Mobile/Tablet: overlay sidebar
      (isMobile || isTablet) && [
        "fixed top-0 left-0 h-full w-80 z-50",
        "transform transition-transform duration-300",
        isOpen ? "translate-x-0" : "-translate-x-full"
      ],
      // Mobile specific adjustments
      isMobile && "w-72"
    )}>
      {/* Mobile/Tablet Close Button */}
      {(isMobile || isTablet) && (
        <div className="flex items-center justify-between p-4 border-b border-gray-200 dark:border-white/5">
          <h2 className="text-lg font-semibold">Menu</h2>
          <button
            onClick={onClose}
            className="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-[#1e2229]"
          >
            <X className="w-5 h-5" />
          </button>
        </div>
      )}

      {/* Sidebar Content */}
      <div className={cn(
        "h-full",
        (isMobile || isTablet) && "pt-0"
      )}>
        {children}
      </div>
    </aside>
  );
};

// Right Sidebar Component  
const RightSidebar: React.FC<{
  children: React.ReactNode;
  isOpen: boolean;
  onClose: () => void;
  isMobile: boolean;
  isTablet: boolean;
}> = ({ children, isOpen, onClose, isMobile, isTablet }) => {
  return (
    <aside className={cn(
      "bg-white dark:bg-[#161920] border-l border-gray-200 dark:border-white/5 transition-all duration-300",
      // Desktop: always visible, fixed width
      !isMobile && !isTablet && "w-80 sticky top-0 h-screen overflow-y-auto",
      // Mobile/Tablet: overlay sidebar
      (isMobile || isTablet) && [
        "fixed top-0 right-0 h-full w-80 z-50",
        "transform transition-transform duration-300",
        isOpen ? "translate-x-0" : "translate-x-full"
      ],
      // Mobile specific adjustments
      isMobile && "w-72"
    )}>
      {/* Mobile/Tablet Close Button */}
      {(isMobile || isTablet) && (
        <div className="flex items-center justify-between p-4 border-b border-gray-200 dark:border-white/5">
          <h2 className="text-lg font-semibold">Widgets</h2>
          <button
            onClick={onClose}
            className="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-[#1e2229]"
          >
            <X className="w-5 h-5" />
          </button>
        </div>
      )}

      {/* Sidebar Content */}
      <div className={cn(
        "h-full",
        (isMobile || isTablet) && "pt-0"
      )}>
        {children}
      </div>
    </aside>
  );
};

// Center Feed Container
const CenterFeedContainer: React.FC<{
  children: React.ReactNode;
}> = ({ children }) => {
  return (
    <div className="max-w-2xl mx-auto py-6">
      {children}
    </div>
  );
};

// Sidebar Toggle Buttons (Mobile/Tablet)
const SidebarToggleButtons: React.FC<{
  leftOpen: boolean;
  rightOpen: boolean;
  onLeftToggle: () => void;
  onRightToggle: () => void;
}> = ({ leftOpen, rightOpen, onLeftToggle, onRightToggle }) => {
  return (
    <>
      {/* Left Sidebar Toggle */}
      {!leftOpen && (
        <button
          onClick={onLeftToggle}
          className="fixed top-20 left-2 z-30 p-2 bg-white dark:bg-[#161920] border border-gray-200 dark:border-white/5 rounded-lg shadow-lg hover:shadow-xl transition-all"
        >
          <ChevronRight className="w-5 h-5" />
        </button>
      )}

      {/* Right Sidebar Toggle */}
      {!rightOpen && (
        <button
          onClick={onRightToggle}
          className="fixed top-20 right-2 z-30 p-2 bg-white dark:bg-[#161920] border border-gray-200 dark:border-white/5 rounded-lg shadow-lg hover:shadow-xl transition-all"
        >
          <ChevronLeft className="w-5 h-5" />
        </button>
      )}
    </>
  );
};

// Default Search Bar
const DefaultSearchBar: React.FC = () => {
  const [searchQuery, setSearchQuery] = useState('');

  return (
    <div className="relative">
      <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
      <input
        type="text"
        placeholder="Search posts, people, events..."
        value={searchQuery}
        onChange={(e) => setSearchQuery(e.target.value)}
        className="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-[#1e2229] text-gray-900 dark:text-white"
      />
    </div>
  );
};

export { ThreePaneFeedLayout, MobileHeader, LeftSidebar, RightSidebar, CenterFeedContainer };