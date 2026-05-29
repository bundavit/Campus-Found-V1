import { isSupabaseConfigured } from '../lib/supabaseClient';

function SupabaseSetupBanner() {
  if (isSupabaseConfigured) return null;

  return (
    <div className="alert alert-warning border-0 rounded-0 mb-0 text-center fw-semibold" role="alert">
      Database not connected — add <code>VITE_SUPABASE_URL</code> and{' '}
      <code>VITE_SUPABASE_ANON_KEY</code> to a <code>.env</code> file in the project root, then restart the dev server.
    </div>
  );
}

export default SupabaseSetupBanner;
