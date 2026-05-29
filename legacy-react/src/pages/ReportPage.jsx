import React, { useState, useEffect } from 'react';
import { supabase } from '../lib/supabaseClient';
import { useNavigate } from 'react-router-dom';

function ReportPage() {
  const navigate = useNavigate();
  const [uploading, setUploading] = useState(false);
  
  const [title, setTitle] = useState('');
  const [status, setStatus] = useState('lost');
  // Formats date for the datetime-local input
  const [dateTime, setDateTime] = useState(new Date().toISOString().slice(0, 16));
  const [location, setLocation] = useState('');
  const [contact, setContact] = useState('');
  const [imageFile, setImageFile] = useState(null);
  const [description, setDescription] = useState('');

  useEffect(() => {
    if (status === 'found') {
      const now = new Date().toISOString().slice(0, 16);
      setDateTime(now);
    }
  }, [status]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!supabase) {
      alert('Database is not configured. Add Supabase keys to a .env file and restart the dev server.');
      return;
    }
    setUploading(true);

    let imageUrl = '';
    if (imageFile) {
      const fileExt = imageFile.name.split('.').pop();
      const fileName = `${Math.random()}.${fileExt}`;
      const { data: uploadData } = await supabase.storage.from('item-images').upload(fileName, imageFile);
      if (uploadData) {
        const { data } = supabase.storage.from('item-images').getPublicUrl(fileName);
        imageUrl = data.publicUrl;
      }
    }

    const { error } = await supabase.from('items').insert([{
      title,
      status,
      created_at: dateTime, // This sends the date/time to Supabase
      location,
      contact_info: contact,
      description,
      image_url: imageUrl
    }]);

    if (error) {
      alert("Error: " + error.message);
    } else {
      alert("Report submitted successfully!");
      navigate('/board');
    }
    setUploading(false);
  };

  return (
    <div className="bg-white min-vh-100 pb-5">
      <div className="text-white py-4 text-center shadow-sm mb-4" style={{ background: '#0d6efd', borderBottom: '5px solid #ffc107' }}>
        <h2 className="fw-bold text-uppercase">Report an Item</h2>
        <p className="opacity-100 fw-bold small">Help the RUPP community stay connected.</p>
      </div>

      <div className="container">
        <div className="row justify-content-center">
          <div className="col-11 col-md-8 col-lg-6">
            <div className="card shadow-lg border border-3 border-dark p-2" style={{ borderRadius: '25px' }}>
              <div className="card-body">
                <form onSubmit={handleSubmit}>
                  <div className="mb-3">
                    <label className="form-label fw-bold">Item Name</label>
                    <input type="text" className="form-control border-2 border-dark rounded-3" placeholder="Ex: Blue Wallet, Student ID" required onChange={(e)=>setTitle(e.target.value)} />
                  </div>

                  <div className="row g-2 mb-3">
                    <div className="col-6">
                      <label className="form-label fw-bold">Status</label>
                      <select className="form-select border-2 border-dark rounded-3" value={status} onChange={(e)=>setStatus(e.target.value)}>
                        <option value="lost">Lost Item</option>
                        <option value="found">Found Item</option>
                      </select>
                    </div>
                    {/* ADDED DATE & TIME INPUT HERE */}
                    <div className="col-6">
                      <label className="form-label fw-bold">Date & Time</label>
                      <input 
                        type="datetime-local" 
                        className="form-control border-2 border-dark rounded-3" 
                        value={dateTime} 
                        onChange={(e) => setDateTime(e.target.value)} 
                        required 
                      />
                    </div>
                  </div>

                  <div className="mb-3">
                    <label className="form-label fw-bold">Location</label>
                    <input type="text" className="form-control border-2 border-dark rounded-3" placeholder="Ex: Building A" required onChange={(e)=>setLocation(e.target.value)} />
                  </div>

                  <div className="mb-3">
                    <label className="form-label fw-bold">Contact Info</label>
                    <input type="text" className="form-control border-2 border-dark rounded-3" placeholder="Telegram or Phone number" required onChange={(e)=>setContact(e.target.value)} />
                  </div>

                  <div className="mb-3">
                    <label className="form-label fw-bold">Description (Optional)</label>
                    <textarea 
                      className="form-control border-2 border-dark rounded-3" 
                      rows="2" 
                      placeholder="Color, brand, or unique marks..."
                      onChange={(e)=>setDescription(e.target.value)}
                    ></textarea>
                  </div>

                  <div className="mb-4">
                    <label className="form-label fw-bold">Attach Photo</label>
                    <input type="file" className="form-control border-2 border-dark rounded-3" accept="image/*" onChange={(e)=>setImageFile(e.target.files[0])} />
                  </div>

                  <button type="submit" className="btn btn-primary w-100 fw-bold py-3 shadow-sm rounded-pill border-3 border-dark" disabled={uploading}>
                    {uploading ? 'SUBMITTING...' : 'SUBMIT REPORT'}
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export default ReportPage;