import { useContext } from 'react';
import { PwaContext } from '../PwaApp';

export function useAuth() {
    return useContext(PwaContext);
}
