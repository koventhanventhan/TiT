import React from 'react'
import { FiStar } from 'react-icons/fi'
import './Testimonials.css'

const Testimonials = () => {
  const testimonials = [
    {
      name: 'K. Ellakiya',
      location: 'Kandy',
      rating: 5,
      text: "EduLearn's platform is incredibly user-friendly. I can access my courses and materials for the Sri Lankan National Syllabus anytime, which has made learning much more flexible for me"
    },
    {
      name: 'P. Vijithan',
      location: 'Colombo',
      rating: 5,
      text: "EduLearn! Their Cambridge courses are great, but the best part is the online forums. Met so many cool classmates from around the world – studying just got way more fun."
    },
    {
      name: 'T. Kalaivani',
      location: 'Kalmunai',
      rating: 5,
      text: "EduLearn is perfect for working moms like me! Their online platform lets my daughter learn anytime, anywhere. No more scrambling to find time for tuition classes!"
    },
    {
      name: 'A. Chellakumar',
      location: 'Galle',
      rating: 5,
      text: "EduLearn provides affordable and high-quality online courses that cater to a diverse range of needs. We receive regular updates on our daughter's progress, allowing us to stay actively involved in her learning journey."
    },
    {
      name: 'C. Kajansika',
      location: 'Batticaloa',
      rating: 5,
      text: "What I love about EduLearn is the community. Even online, I feel connected to my peers and teachers. It's not just about passing exams; it's about growing and learning together. The support here is incredible."
    }
  ]

  return (
    <section className="testimonials section">
      <div className="container">
        <div className="testimonials-header">
          <h2 className="section-title">Students & Parents Love Us</h2>
          <p className="section-subtitle">
            See how we transform learning with academic growth, flexibility, and a supportive learning environment! 👩🏻‍🎓
          </p>
        </div>

        <div className="testimonials-grid">
          {testimonials.map((testimonial, index) => (
            <div key={index} className="testimonial-card">
              <div className="testimonial-rating">
                {[...Array(testimonial.rating)].map((_, i) => (
                  <FiStar key={i} className="star filled" />
                ))}
              </div>
              <p className="testimonial-text">"{testimonial.text}"</p>
              <div className="testimonial-author">
                <div className="author-info">
                  <h4 className="author-name">{testimonial.name}</h4>
                  <p className="author-location">{testimonial.location}</p>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}

export default Testimonials

