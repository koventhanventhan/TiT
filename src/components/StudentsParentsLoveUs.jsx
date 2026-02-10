import React from 'react'
import { FiHeart, FiStar, FiUsers, FiAward } from 'react-icons/fi'
import { useSettings } from '../context/SettingsContext'
import './StudentsParentsLoveUs.css'

const StudentsParentsLoveUs = () => {
  const { getSetting } = useSettings();

  const defaultTestimonials = [
    {
      name: 'Sarah Perera',
      role: 'Parent',
      student: 'Grade 10 Student',
      rating: 5,
      comment: 'TiT Online Education has transformed my daughter\'s learning experience. The personalized attention and quality teaching have improved her grades significantly.',
      image: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&h=150&fit=crop'
    },
    {
      name: 'Kamal Fernando',
      role: 'Student',
      student: 'A/L Student',
      rating: 5,
      comment: 'The best online tuition platform! The teachers are excellent and the classes are very interactive. I\'ve improved my exam results dramatically.',
      image: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&h=150&fit=crop'
    },
    {
      name: 'Nimali Silva',
      role: 'Parent',
      student: 'O/L Student',
      rating: 5,
      comment: 'As a parent, I\'m very satisfied with the quality of education. The progress reports and parent-teacher communication are excellent.',
      image: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150&h=150&fit=crop'
    },
    {
      name: 'Ashan Jayawardena',
      role: 'Student',
      student: 'Grade 12 Student',
      rating: 5,
      comment: 'The study materials and past papers are very helpful. The teachers explain concepts clearly and are always available to help.',
      image: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&h=150&fit=crop'
    },
    {
      name: 'Priyanka Perera',
      role: 'Parent',
      student: 'Grade 8 Student',
      rating: 5,
      comment: 'My son loves the interactive classes and the engaging teaching methods. His confidence has increased and he enjoys learning now.',
      image: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=150&h=150&fit=crop'
    },
    {
      name: 'Dilshan Fernando',
      role: 'Student',
      student: 'Grade 11 Student',
      rating: 5,
      comment: 'The platform is user-friendly and the classes are well-structured. I can access recordings anytime which helps with revision.',
      image: 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=150&h=150&fit=crop'
    }
  ]

  let testimonials = defaultTestimonials;
  const testimonialsJson = getSetting('love_us_testimonials');
  if (testimonialsJson) {
    try {
      const parsed = JSON.parse(testimonialsJson);
      if (Array.isArray(parsed) && parsed.length > 0) {
        testimonials = parsed;
      }
    } catch (e) {
      console.error('Failed to parse love_us_testimonials JSON', e);
    }
  }

  const stats = [
    {
      icon: <FiUsers />,
      number: getSetting('love_us_stat1_number', '10,000+'),
      label: getSetting('love_us_stat1_label', 'Happy Students')
    },
    {
      icon: <FiHeart />,
      number: getSetting('love_us_stat2_number', '98%'),
      label: getSetting('love_us_stat2_label', 'Satisfaction Rate')
    },
    {
      icon: <FiStar />,
      number: getSetting('love_us_stat3_number', '4.9/5'),
      label: getSetting('love_us_stat3_label', 'Average Rating')
    },
    {
      icon: <FiAward />,
      number: getSetting('love_us_stat4_number', '95%'),
      label: getSetting('love_us_stat4_label', 'Pass Rate')
    }
  ]

  return (
    <section className="students-parents-love-us section">
      <div className="container">
        <div className="section-header">
          <h2 className="section-title">{getSetting('love_us_title', 'Students & Parents Love Us')}</h2>
          <p className="section-subtitle">
            {getSetting('love_us_subtitle', 'Join thousands of satisfied students and parents who trust TiT Online Education')}
          </p>
        </div>

        {/* Stats Section */}
        <div className="love-us-stats">
          {stats.map((stat, index) => (
            <div key={index} className="love-us-stat-card">
              <div className="love-us-stat-icon">{stat.icon}</div>
              <div className="love-us-stat-number">{stat.number}</div>
              <div className="love-us-stat-label">{stat.label}</div>
            </div>
          ))}
        </div>

        {/* Testimonials Grid */}
        <div className="love-us-testimonials">
          {testimonials.map((testimonial, index) => {
            // Helper to resolve image URL
            const getImageUrl = (img) => {
              if (!img) return 'https://via.placeholder.com/150';
              if (img.startsWith('http')) return img;
              // If it's a relative path, assume it's from the backend
              return `http://127.0.0.1:8000${img.startsWith('/') ? '' : '/'}${img}`;
            };

            return (
              <div key={index} className="love-us-testimonial-card">
                <div className="love-us-testimonial-header">
                  <div className="love-us-testimonial-image">
                    <img
                      src={getImageUrl(testimonial.image)}
                      alt={testimonial.name}
                      onError={(e) => { e.target.src = 'https://via.placeholder.com/150' }}
                    />
                  </div>
                  <div className="love-us-testimonial-info">
                    <h4 className="love-us-testimonial-name">{testimonial.name}</h4>
                    <p className="love-us-testimonial-role">{testimonial.role}</p>
                    <p className="love-us-testimonial-student">{testimonial.student}</p>
                  </div>
                </div>
                <div className="love-us-testimonial-rating">
                  {[...Array(parseInt(testimonial.rating) || 5)].map((_, i) => (
                    <FiStar key={i} className="star-icon filled" />
                  ))}
                </div>
                <p className="love-us-testimonial-comment">"{testimonial.comment}"</p>
              </div>
            );
          })}
        </div>
      </div>
    </section>
  )
}

export default StudentsParentsLoveUs

