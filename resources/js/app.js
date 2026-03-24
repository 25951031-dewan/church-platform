import './bootstrap';
import React from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import App from './ChurchApp';

const queryClient = new QueryClient();

ReactDOM.createRoot(document.getElementById('app')).render(
    React.createElement(React.StrictMode, null,
        React.createElement(BrowserRouter, null,
            React.createElement(QueryClientProvider, { client: queryClient },
                React.createElement(App, null)
            )
        )
    )
);
