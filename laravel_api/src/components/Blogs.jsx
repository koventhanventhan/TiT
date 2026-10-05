import React from 'react'
import { Link } from 'react-router-dom'
import { FiCalendar, FiArrowRight, FiTag } from 'react-icons/fi'
import { useLanguage } from '../context/LanguageContext'
import './Blogs.css'

const Blogs = () => {
  const { t } = useLanguage()
  const [blogTitle, setBlogTitle] = React.useState('')
  const [blogSubtitle, setBlogSubtitle] = React.useState('')
  const [displayBlogs, setDisplayBlogs] = React.useState([])

  React.useEffect(() => {
    const baseBlogs = [
      {
        id: 1,
        title: "Navigating the Educational Disparity: Addressing COVID-19's Impact on Student Learning and Mental Health",
        category: t('cat_digital_edu'),
        date: "April 23, 2024",
        excerpt: "Exploring how the pandemic has reshaped education and the importance of addressing learning gaps and mental health challenges.",
        image: "📚",
        readTime: `5 ${t('blog_read_time')}`
      },
      {
        id: 2,
        title: "Stepping Stone to New Art of Digital Learning – Founder of EDUS Online Institute",
        category: t('cat_digital_edu'),
        date: "April 15, 2024",
        excerpt: "An insightful interview with the founder about the future of digital learning and innovative educational approaches.",
        image: "💡",
        readTime: `7 ${t('blog_read_time')}`
      },
      {
        id: 3,
        title: "Effective Self-Care Strategies for Teachers",
        category: t('cat_self_care'),
        date: "April 15, 2024",
        excerpt: "Essential self-care practices for educators to maintain well-being and prevent burnout in the teaching profession.",
        image: "🧘",
        readTime: `4 ${t('blog_read_time')}`
      },
      {
        id: 4,
        title: "Top 10 Effective Study Tips for Sri Lankan Students in 2024",
        category: t('cat_study_tips'),
        date: "April 15, 2024",
        excerpt: "Practical and proven study techniques tailored for Sri Lankan students to excel in their academic journey.",
        image: "🎯",
        readTime: `6 ${t('blog_read_time')}`
      }
    ]

    if (language !== 'en') {
      const translateBlogs = async () => {
        setBlogTitle(t('blog_title'))
        setBlogSubtitle(t('blog_subtitle'))
        
        const translated = await Promise.all(baseBlogs.map(async (b) => ({
          ...b,
          title: await translate(b.title),
          excerpt: await translate(b.excerpt),
          category: t(b.id <= 2 ? 'cat_digital_edu' : (b.id === 3 ? 'cat_self_care' : 'cat_study_tips'))
        })))
        setDisplayBlogs(translated)
      }
      translateBlogs()
    } else {
      setBlogTitle(t('blog_title'))
      setBlogSubtitle(t('blog_subtitle'))
      setDisplayBlogs(baseBlogs)
    }
  }, [language, translate, t])

  return (
    <section id="blogs" className="blogs section">
      <div className="container">
        <div className="blogs-header">
          <h2 className="section-title">{blogTitle}</h2>
          <p className="section-subtitle">
            {blogSubtitle}
          </p>
        </div>

        <div className="blogs-grid">
          {displayBlogs.map((blog) => (
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
                  {t('blog_read_more')}
                  <FiArrowRight />
                </Link>
              </div>
            </article>
          ))}
        </div>

        <div className="blogs-cta">
          <p>{t('blog_newsletter_title')}</p>
          <a href="#register" className="btn btn-primary">
            {t('blog_newsletter_btn')}
          </a>
        </div>
      </div>
    </section>
  )
}

export default Blogs

