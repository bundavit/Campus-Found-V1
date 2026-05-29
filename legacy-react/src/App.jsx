import { Routes, Route, useLocation } from 'react-router-dom';
import HomePage from './pages/HomePage';
import BoardPage from './pages/BoardPage';
import ReportPage from './pages/ReportPage';
import LoginPage from './pages/LoginPage';
import AdminDashboard from './pages/AdminDashboard';
import Navbar from './components/Navbar';
import SupabaseSetupBanner from './components/SupabaseSetupBanner';

function App() {
  const location = useLocation();
  const isAdminPath = location.pathname.toLowerCase().includes('admin');

  return (
    <>
      <SupabaseSetupBanner />
      {!isAdminPath && <Navbar />} 
      <Routes>
        <Route path="/" element={<HomePage />} />
        <Route path="/board" element={<BoardPage />} />
        <Route path="/report" element={<ReportPage />} />
        <Route path="/LoginPage" element={<LoginPage />} /> 
        <Route path="/AdminDashboard" element={<AdminDashboard />} />
      </Routes>
    </>
  );
}

export default App;