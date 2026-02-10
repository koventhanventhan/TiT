import React from 'react'
import { Link } from 'react-router-dom'
import { FiCalendar, FiArrowRight, FiTag } from 'react-icons/fi'
import './Blogs.css'

const Blogs = () => {
  const blogs = [
    {
      id: 1,
      title: "Navigating the Educational Disparity: Addressing COVID-19's Impact on Student Learning and Mental Health",
      category: "Digital Education Platform",
      date: "April 23, 2024",
      excerpt: "Exploring how the pandemic has reshaped education and the importance of addressing learning gaps and mental health challenges.",
      image: "📚",
      readTime: "5 min read"
    },
    {
      id: 2,
      title: "Stepping Stone to New Art of Digital Learning – Founder of EDUS Online Institute",
      category: "Digital Education Platform",
      date: "April 15, 2024",
      excerpt: "An insightful interview with the founder about the future of digital learning and innovative educational approaches.",
      image: "💡",
      readTime: "7 min read"
    },
    {
      id: 3,
      title: "Effective Self-Care Strategies for Teachers",
      category: "Teacher Self Care Strategies",
      date: "April 15, 2024",
      excerpt: "Essential self-care practices for educators to maintain well-being and prevent burnout in the teaching profession.",
      image: "🧘",
      readTime: "4 min read"
    },
    {
      id: 4,
      title: "Top 10 Effective Study Tips for Sri Lankan Students in 2024",
      category: "Study Tips",
      date: "April 15, 2024",
      excerpt: "Practical and proven study techniques tailored for Sri Lankan students to excel in their academic journey.",
      image: "🎯",
      readTime: "6 min read"
    }
  ]

  return (
    <section id="blogs" className="blogs section">
      <div className="container">
        <div className="blogs-header">
          <h2 className="section-title">Blogs</h2>
          <p className="section-subtitle">
            Stay updated with the latest insights, tips, and stories from the world of online education
          </p>
        </div>

        <div className="blogs-grid">
          {blogs.map((blog) => (
            <article key={blog.id} className="blog-card">
              <div className="blog-image">
                <div className="blog-image-placeholder">
                  <span className="blog-emoji">{blog.image}</span>
                </div>
                <div className="blog-category-badge">
                  <FiTag />
                  <span>{blog.category}</span>
                </div>
              </div>
              
              <div className="blog-content">
                <div className="blog-meta">
                  <span className="blog-date">
                    <FiCalendar />
                    {blog.date}
                  </span>
                  <span className="blog-read-time">{blog.readTime}</span>
                </div>
                
                <h3 className="blog-title">{blog.title}</h3>
                <p className="blog-excerpt">{blog.excerpt}</p>
                
                <Link to={`/blog/${blog.id}`} className="blog-link">
                  Continue Reading
                  <FiArrowRight />
                </Link>
              </div>
            </article>
          ))}
        </div>

        <div className="blogs-cta">
          <p>Want to stay updated with our latest blogs?</p>
          <a href="#register" className="btn btn-primary">
            Subscribe to Newsletter
          </a>
        </div>
      </div>
    </section>
  )
}

export default Blogs

