import { SelectHTMLAttributes, forwardRef, ReactNode } from 'react';
import { cn } from '@app/common/utils/cn';

export interface SelectProps extends SelectHTMLAttributes<HTMLSelectElement> {
  children: ReactNode;
}

const Select = forwardRef<HTMLSelectElement, SelectProps>(
  ({ className, children, ...props }, ref) => {
    return (
      <select
        className={cn(
          'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
          className
        )}
        ref={ref}
        {...props}
      >
        {children}
      </select>
    );
  }
);

Select.displayName = 'Select';

// For advanced select components (keeping API compatible)
const SelectContent = forwardRef<HTMLDivElement, { children: ReactNode }>(
  ({ children }, ref) => (
    <div ref={ref}>{children}</div>
  )
);

const SelectItem = forwardRef<HTMLOptionElement, { value: string; children: ReactNode }>(
  ({ value, children }, ref) => (
    <option ref={ref} value={value}>{children}</option>
  )
);

const SelectTrigger = forwardRef<HTMLDivElement, { children: ReactNode; className?: string }>(
  ({ children, className }, ref) => (
    <div ref={ref} className={className}>{children}</div>
  )
);

const SelectValue = forwardRef<HTMLSpanElement, { placeholder?: string }>(
  ({ placeholder }, ref) => (
    <span ref={ref}>{placeholder}</span>
  )
);

export { Select, SelectContent, SelectItem, SelectTrigger, SelectValue };