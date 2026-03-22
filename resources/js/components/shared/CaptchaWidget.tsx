import { Turnstile } from '@marsidev/react-turnstile';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { forwardRef } from 'react';

interface CaptchaSettings {
    captcha_enabled: boolean;
    turnstile_site_key: string | null;
}

interface CaptchaWidgetProps {
    onVerify: (token: string) => void;
    onError?: () => void;
    onExpire?: () => void;
    theme?: 'light' | 'dark' | 'auto';
}

/**
 * CaptchaWidget — renders a Cloudflare Turnstile widget only when
 * captcha is enabled in platform settings. Pass `onVerify` to capture
 * the token for form submission.
 *
 * Usage:
 *   <CaptchaWidget onVerify={token => setToken(token)} />
 *   // Include token as `cf-turnstile-response` in your API request.
 */
const CaptchaWidget = forwardRef<HTMLDivElement, CaptchaWidgetProps>(
    ({ onVerify, onError, onExpire, theme = 'auto' }, ref) => {
        const { data } = useQuery<CaptchaSettings>({
            queryKey: ['captcha-settings-public'],
            queryFn:  () => axios.get('/api/v1/captcha/config').then(r => r.data),
            staleTime: 5 * 60 * 1000,
        });

        if (! data?.captcha_enabled || ! data.turnstile_site_key) {
            return null;
        }

        return (
            <div ref={ref}>
                <Turnstile
                    siteKey={data.turnstile_site_key}
                    onSuccess={onVerify}
                    onError={onError}
                    onExpire={onExpire}
                    options={{ theme }}
                />
            </div>
        );
    }
);

CaptchaWidget.displayName = 'CaptchaWidget';

export default CaptchaWidget;
