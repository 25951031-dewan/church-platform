import { ReactNode, useState, createContext, useContext } from 'react';

interface CollapsibleContextType {
  open: boolean;
  setOpen: (open: boolean) => void;
}

const CollapsibleContext = createContext<CollapsibleContextType>({
  open: false,
  setOpen: () => {},
});

interface CollapsibleProps {
  children: ReactNode;
  open?: boolean;
  onOpenChange?: (open: boolean) => void;
}

const Collapsible = ({ children, open: controlledOpen, onOpenChange }: CollapsibleProps) => {
  const [internalOpen, setInternalOpen] = useState(false);
  const isControlled = controlledOpen !== undefined;
  const open = isControlled ? controlledOpen : internalOpen;
  
  const setOpen = (newOpen: boolean) => {
    if (isControlled) {
      onOpenChange?.(newOpen);
    } else {
      setInternalOpen(newOpen);
    }
  };

  return (
    <CollapsibleContext.Provider value={{ open, setOpen }}>
      <div>{children}</div>
    </CollapsibleContext.Provider>
  );
};

const CollapsibleTrigger = ({ children, ...props }: { children: ReactNode }) => {
  const { open, setOpen } = useContext(CollapsibleContext);
  
  return (
    <button onClick={() => setOpen(!open)} {...props}>
      {children}
    </button>
  );
};

const CollapsibleContent = ({ children }: { children: ReactNode }) => {
  const { open } = useContext(CollapsibleContext);
  
  if (!open) return null;
  
  return <div>{children}</div>;
};

export { Collapsible, CollapsibleTrigger, CollapsibleContent };