import React, { useEffect, useState } from 'react';
import { supabase } from '../lib/supabaseClient';

function BoardPage() {
  const [items, setItems] = useState([]);
  const [filter, setFilter] = useState('all');
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedItem, setSelectedItem] = useState(null);
  const [sortOrder, setSortOrder] = useState('desc');
  const [selectedDate, setSelectedDate] = useState('');

  const formatRelativeTime = (dateString) => {
    if (!dateString) return '';
    const now = new Date();
    const postDate = new Date(dateString);
    const diffInSeconds = Math.floor((now - postDate) / 1000);
    if (diffInSeconds < 60) return 'Just now';
    const diffInMinutes = Math.floor(diffInSeconds / 60);
    if (diffInMinutes < 60) return `${diffInMinutes}m ago`;
    const diffInHours = Math.floor(diffInMinutes / 60);
    if (diffInHours < 24) return `${diffInHours}h ago`;
    const diffInDays = Math.floor(diffInHours / 24);
    if (diffInDays === 1) return 'Yesterday';
    if (diffInDays < 7) return `${diffInDays} days ago`;
    return postDate.toLocaleDateString(); 
  };

  useEffect(() => {
    const fetchItems = async () => {
      if (!supabase) return;
      let query = supabase
        .from('items')
        .select('*')
        .order('created_at', { ascending: sortOrder === 'asc' });

      if (filter !== 'all') query = query.eq('status', filter);

      if (selectedDate) {
        const startOfDay = `${selectedDate}T00:00:00`;
        const endOfDay = `${selectedDate}T23:59:59`;
        query = query.gte('created_at', startOfDay).lte('created_at', endOfDay);
      }
      
      const { data } = await query;
      setItems(data || []);
    };
    fetchItems();
  }, [filter, sortOrder, selectedDate]);

  const filteredItems = items.filter((item) =>
    item.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
    item.location.toLowerCase().includes(searchTerm.toLowerCase())
  );

  // --- COOL FRAME LOGIC ---
  const getBtnStyle = (type) => {
    const isActive = filter === type;
    
    // Define theme colors
    const colors = {
      all: '#212529',   // Black
      lost: '#dc3545',  // Red
      found: '#198754'  // Green
    };

    const themeColor = colors[type];

    return {
      borderRadius: '12px',
      minWidth: '115px',
      // The border now matches the theme color!
      border: `3px solid ${themeColor}`, 
      backgroundColor: isActive ? themeColor : '#ffffff',
      color: isActive ? '#ffffff' : themeColor,
      transition: 'all 0.25s ease',
      fontWeight: '800', // Extra bold for that cool look
      letterSpacing: '1px'
    };
  };

  return (
    <div className="min-vh-100 bg-white pb-5">
      {/* HERO SECTION */}
      <div className="text-white shadow-sm mb-4" style={{ background: '#0d6efd', borderBottom: '5px solid #ffc107' }}>
        <div className="container py-5 text-center">
          <h1 className="display-5 fw-bold text-uppercase">RUPP Community Board</h1>
          <p className="lead fw-bold">Find your belongings within the RUPP community.</p>
        </div>
      </div>

      <div className="container">
        {/* SEARCH & FILTERS */}
        <div className="row g-2 mb-4 justify-content-center px-2">
          <div className="col-12 col-lg-8">
            <div className="input-group input-group-lg shadow-sm border border-2 border-dark rounded">
              <span className="input-group-text bg-white border-0">🔍</span>
              <input 
                type="text" 
                className="form-control border-0" 
                placeholder="Search name or location..." 
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </div>
          </div>
        </div>

        {/* --- CATEGORY BUTTONS WITH MATCHING FRAMES --- */}
        <div className="d-flex flex-wrap gap-3 justify-content-center mb-5 px-2">
          {['all', 'lost', 'found'].map((type) => {
            const themeColor = type === 'lost' ? '#dc3545' : type === 'found' ? '#198754' : '#212529';
            
            return (
              <button 
                key={type}
                onClick={() => setFilter(type)} 
                style={getBtnStyle(type)}
                className="btn btn-lg px-4 py-2"
                onMouseEnter={(e) => {
                  e.currentTarget.style.backgroundColor = themeColor;
                  e.currentTarget.style.color = '#ffffff';
                }}
                onMouseLeave={(e) => {
                  if (filter !== type) {
                    e.currentTarget.style.backgroundColor = '#ffffff';
                    e.currentTarget.style.color = themeColor;
                  }
                }}
              >
                {type.toUpperCase()}
              </button>
            );
          })}
        </div>

        {/* BOARD GRID: CENTERED & FRAMED */}
        <div className="row g-3 g-md-4 justify-content-center px-2">
          {filteredItems.map(item => (
            <div key={item.id} className="col-6 col-md-4 col-lg-3 d-flex justify-content-center">
              <div 
                className="card h-100 border-2 border-dark shadow-sm overflow-hidden" 
                style={{ borderRadius: '15px', cursor: 'pointer', maxWidth: '300px', width: '100%' }} 
                onClick={() => setSelectedItem(item)}
              >
                <div className="position-relative">
                  <img src={item.image_url || 'https://via.placeholder.com/300'} style={{ height: '180px', width: '100%', objectFit: 'cover' }} alt={item.title} />
                  <span className={`position-absolute top-0 end-0 m-2 badge rounded-pill border border-1 border-white ${item.status === 'lost' ? 'bg-danger' : 'bg-success'}`}>
                    {item.status.toUpperCase()}
                  </span>
                </div>
                <div className="card-body p-3">
                  <h6 className="fw-bold mb-1 text-truncate">{item.title}</h6>
                  <p className="text-muted small mb-2 text-truncate">📍 {item.location}</p>
                  <div className="d-flex justify-content-between align-items-center pt-2 border-top border-dark">
                    <small className="fw-bold text-secondary" style={{ fontSize: '0.7rem' }}>{formatRelativeTime(item.created_at)}</small>
                    <small className="fw-bold text-primary">Details →</small>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* MODAL (Keeping same frame style) */}
      {/* MODAL - NOW AS COOL AS HOMEPAGE */}
{selectedItem && (
  <div className="modal show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.85)', zIndex: 3000 }} onClick={() => setSelectedItem(null)}>
    <div className="modal-dialog modal-dialog-centered" onClick={(e) => e.stopPropagation()}>
      <div className="modal-content border border-3 border-dark shadow-lg" style={{ borderRadius: '25px', overflow: 'hidden' }}>
        <div className="modal-body p-0 text-dark">
          {selectedItem.image_url && (
  <div className="bg-light border-bottom border-2 border-dark" style={{ height: '400px', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
    <img 
      src={selectedItem.image_url} 
      className="w-100 h-100" 
      style={{ objectFit: 'contain' }} // This prevents cropping
      alt={selectedItem.title} 
    />
  </div>
)}
<div className="p-4">
            <div className="d-flex justify-content-between align-items-center mb-2">
              <h2 className="fw-bold m-0">{selectedItem.title}</h2>
              <span className={`badge px-3 py-2 rounded-pill border border-2 border-dark ${selectedItem.status === 'lost' ? 'bg-danger text-white' : 'bg-success text-white'}`}>
                {selectedItem.status.toUpperCase()}
              </span>
            </div>
            {/* Added Date Display */}
            <p className="text-muted mb-4 fs-6 fw-bold">📅 {new Date(selectedItem.created_at).toLocaleDateString()}</p>
            
            <hr className="border-2 border-dark" />
            
            {/* Added Location Section */}
            <div className="mb-3">
              <label className="fw-bold text-primary small text-uppercase mb-1 d-block">Location</label>
              <p className="fs-5 fw-bold">📍 {selectedItem.location}</p>
            </div>

            {/* Added Description Section */}
            <div className="mb-3">
              <label className="fw-bold text-primary small text-uppercase mb-1 d-block">Description</label>
              <p className="bg-light p-3 rounded-3 border border-dark">{selectedItem.description || 'No description provided.'}</p>
            </div>

            <div className="bg-primary text-white p-3 rounded-4 shadow-sm border border-2 border-dark text-center">
              <label className="fw-bold small text-uppercase mb-1 d-block opacity-75">Contact Details</label>
              <p className="m-0 fs-4 fw-bold">{selectedItem.contact_info}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
)}
    </div>
  );
}

export default BoardPage;