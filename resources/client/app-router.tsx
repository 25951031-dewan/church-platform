import { Routes, Route } from 'react-router';

export function AppRouter() {
  return (
    <Routes>
      <Route path="/" element={<div className="p-8 text-2xl">Church Platform v5 — Foundation</div>} />
    </Routes>
  );
}
