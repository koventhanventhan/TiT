import React from 'react'
import { useParams, Link } from 'react-router-dom'
import { FiCalendar, FiTag, FiArrowLeft, FiUser, FiClock, FiShare2, FiFacebook, FiTwitter, FiLinkedin } from 'react-icons/fi'
import './BlogPage.css'

const BlogPage = () => {
  const { id } = useParams()

  // Blog data - in real app, this would come from API
  const blogs = {
    1: {
      id: 1,
      title: "Navigating the Educational Disparity: Addressing COVID-19's Impact on Student Learning and Mental Health",
      category: "Digital Education Platform",
      date: "April 23, 2024",
      author: "Dr. Sarah Perera",
      readTime: "5 min read",
      image: "📚",
      content: `
        <p>The COVID-19 pandemic has fundamentally transformed the educational landscape, creating unprecedented challenges and opportunities for students, educators, and institutions worldwide. As we navigate through these changes, it's crucial to address both the learning gaps and the mental health implications that have emerged.</p>
        
        <h2>The Learning Disparity Challenge</h2>
        <p>The shift to online learning has revealed significant disparities in access to technology, internet connectivity, and learning resources. Students from underserved communities have faced greater challenges in adapting to digital learning environments, leading to widening achievement gaps.</p>
        
        <p>Research indicates that students who lacked adequate support during the pandemic are now facing learning losses equivalent to several months of instruction. This has particularly affected subjects requiring hands-on practice and collaborative learning.</p>
        
        <h2>Mental Health Considerations</h2>
        <p>The isolation and uncertainty brought about by the pandemic have taken a toll on students' mental health. Many have reported increased anxiety, depression, and feelings of disconnection from their peers and educational community.</p>
        
        <p>Educational institutions must prioritize mental health support services and create safe spaces for students to express their concerns and seek help. This includes:</p>
        <ul>
          <li>Accessible counseling services</li>
          <li>Peer support programs</li>
          <li>Mental health awareness campaigns</li>
          <li>Flexible learning accommodations</li>
        </ul>
        
        <h2>Moving Forward</h2>
        <p>As we move forward, it's essential to implement comprehensive strategies that address both academic recovery and mental well-being. This includes personalized learning plans, increased support for struggling students, and ongoing mental health resources.</p>
        
        <p>The future of education lies in creating inclusive, supportive environments that recognize the interconnectedness of academic success and mental health. By addressing these challenges holistically, we can help students not only recover from the pandemic's impact but also thrive in their educational journeys.</p>
      `
    },
    2: {
      id: 2,
      title: "Stepping Stone to New Art of Digital Learning – Founder of EDUS Online Institute",
      category: "Digital Education Platform",
      date: "April 15, 2024",
      author: "Interview Team",
      readTime: "7 min read",
      image: "💡",
      content: `
        <p>In an exclusive interview, we sat down with the founder of EDUS Online Institute to discuss the future of digital learning and the innovative approaches shaping modern education.</p>
        
        <h2>The Vision Behind EDUS</h2>
        <p>"Education should be accessible, engaging, and transformative," says the founder. "Our mission is to break down barriers and create learning experiences that inspire students to reach their full potential."</p>
        
        <p>The institute was founded on the principle that quality education should not be limited by geographical boundaries or traditional classroom constraints. By leveraging technology, EDUS has created a platform that brings world-class education to students across Sri Lanka and beyond.</p>
        
        <h2>Innovative Learning Approaches</h2>
        <p>The platform incorporates several innovative features designed to enhance the learning experience:</p>
        <ul>
          <li>Interactive live sessions with real-time feedback</li>
          <li>AI-powered personalized learning paths</li>
          <li>Gamified learning experiences</li>
          <li>Comprehensive resource libraries</li>
        </ul>
        
        <h2>Looking Ahead</h2>
        <p>As digital learning continues to evolve, EDUS remains committed to staying at the forefront of educational innovation. The future holds exciting possibilities for virtual reality classrooms, advanced AI tutoring, and even more personalized learning experiences.</p>
      `
    },
    3: {
      id: 3,
      title: "Effective Self-Care Strategies for Teachers",
      category: "Teacher Self Care Strategies",
      date: "April 15, 2024",
      author: "Dr. Priya Fernando",
      readTime: "4 min read",
      image: "🧘",
      content: `
        <p>Teaching is one of the most rewarding yet demanding professions. Educators often prioritize their students' well-being while neglecting their own. However, self-care is not a luxury—it's a necessity for maintaining effectiveness and passion in the classroom.</p>
        
        <h2>Why Teacher Self-Care Matters</h2>
        <p>When teachers are well-rested, mentally healthy, and emotionally balanced, they can provide better support to their students. Self-care helps prevent burnout, reduces stress, and maintains enthusiasm for teaching.</p>
        
        <h2>Practical Self-Care Strategies</h2>
        <h3>1. Set Boundaries</h3>
        <p>Learn to say no and establish clear boundaries between work and personal time. Avoid checking emails or grading papers during your personal hours.</p>
        
        <h3>2. Practice Mindfulness</h3>
        <p>Incorporate mindfulness practices into your daily routine. Even 10 minutes of meditation or deep breathing can significantly reduce stress levels.</p>
        
        <h3>3. Stay Physically Active</h3>
        <p>Regular exercise is crucial for both physical and mental health. Find activities you enjoy, whether it's yoga, walking, or dancing.</p>
        
        <h3>4. Connect with Colleagues</h3>
        <p>Build a support network with fellow educators. Share experiences, seek advice, and celebrate successes together.</p>
        
        <h3>5. Pursue Hobbies</h3>
        <p>Make time for activities outside of teaching that bring you joy and fulfillment.</p>
        
        <h2>Remember</h2>
        <p>Taking care of yourself is not selfish—it's essential. By prioritizing your well-being, you're better equipped to support your students and maintain your passion for education.</p>
      `
    },
    4: {
      id: 4,
      title: "Top 10 Effective Study Tips for Sri Lankan Students in 2024",
      category: "Study Tips",
      date: "April 15, 2024",
      author: "Academic Success Team",
      readTime: "6 min read",
      image: "🎯",
      content: `
        <p>Success in academics requires more than just attending classes and completing assignments. Here are the top 10 proven study strategies specifically tailored for Sri Lankan students.</p>
        
        <h2>1. Create a Study Schedule</h2>
        <p>Plan your study time in advance. Allocate specific hours for each subject and stick to your schedule. Consistency is key to effective learning.</p>
        
        <h2>2. Find Your Optimal Study Environment</h2>
        <p>Identify a quiet, well-lit space free from distractions. Some students prefer complete silence, while others work better with soft background music.</p>
        
        <h2>3. Use Active Learning Techniques</h2>
        <p>Instead of passively reading, engage with the material. Take notes, create mind maps, explain concepts aloud, or teach someone else.</p>
        
        <h2>4. Practice Past Papers</h2>
        <p>For Sri Lankan students preparing for O/L and A/L exams, practicing past papers is crucial. It helps you understand the exam format and identify areas needing improvement.</p>
        
        <h2>5. Take Regular Breaks</h2>
        <p>Follow the Pomodoro Technique: study for 25 minutes, then take a 5-minute break. This prevents mental fatigue and improves retention.</p>
        
        <h2>6. Form Study Groups</h2>
        <p>Collaborate with classmates to discuss difficult concepts, quiz each other, and share different perspectives on the material.</p>
        
        <h2>7. Use Technology Wisely</h2>
        <p>Leverage educational apps, online resources, and digital tools to enhance your learning. However, avoid distractions from social media during study time.</p>
        
        <h2>8. Get Adequate Sleep</h2>
        <p>Aim for 7-9 hours of sleep each night. Sleep is essential for memory consolidation and cognitive function.</p>
        
        <h2>9. Stay Organized</h2>
        <p>Keep your notes, assignments, and study materials well-organized. Use folders, digital tools, or apps to manage your academic resources.</p>
        
        <h2>10. Maintain a Healthy Lifestyle</h2>
        <p>Eat nutritious meals, stay hydrated, and exercise regularly. Physical health directly impacts your ability to learn and retain information.</p>
        
        <h2>Conclusion</h2>
        <p>Remember, effective studying is a skill that improves with practice. Experiment with different techniques to find what works best for you, and don't hesitate to seek help when needed.</p>
      `
    }
  }

  const blog = blogs[id] || blogs[1]

  if (!blog) {
    return (
      <div className="blog-page">
        <div className="container">
          <div className="blog-not-found">
            <h1>Blog Not Found</h1>
            <p>The blog post you're looking for doesn't exist.</p>
            <Link to="/" className="btn-back">
              <FiArrowLeft />
              Back to Home
            </Link>
          </div>
        </div>
      </div>
    )
  }

  return (
    <div className="blog-page">
      <div className="blog-hero">
        <div className="container">
          <Link to="/" className="back-link">
            <FiArrowLeft />
            Back to Home
          </Link>
          <div className="blog-hero-content">
            <div className="blog-category-badge">
              <FiTag />
              <span>{blog.category}</span>
            </div>
            <h1 className="blog-title">{blog.title}</h1>
            <div className="blog-meta">
              <div className="meta-item">
                <FiUser />
                <span>{blog.author}</span>
              </div>
              <div className="meta-item">
                <FiCalendar />
                <span>{blog.date}</span>
              </div>
              <div className="meta-item">
                <FiClock />
                <span>{blog.readTime}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="container">
        <div className="blog-content-wrapper">
          <article className="blog-article">
            <div className="blog-image-header">
              <div className="blog-image-placeholder">
                <span className="blog-emoji">{blog.image}</span>
              </div>
            </div>

            <div 
              className="blog-content"
              dangerouslySetInnerHTML={{ __html: blog.content }}
            />

            <div className="blog-share-section">
              <h3>Share this article</h3>
              <div className="share-buttons">
                <a href={`https://www.facebook.com/sharer/sharer.php?u=${window.location.href}`} target="_blank" rel="noopener noreferrer" className="share-btn facebook">
                  <FiFacebook />
                  Facebook
                </a>
                <a href={`https://twitter.com/intent/tweet?url=${window.location.href}`} target="_blank" rel="noopener noreferrer" className="share-btn twitter">
                  <FiTwitter />
                  Twitter
                </a>
                <a href={`https://www.linkedin.com/sharing/share-offsite/?url=${window.location.href}`} target="_blank" rel="noopener noreferrer" className="share-btn linkedin">
                  <FiLinkedin />
                  LinkedIn
                </a>
              </div>
            </div>
          </article>

          <aside className="blog-sidebar">
            <div className="sidebar-card">
              <h3>Related Articles</h3>
              <div className="related-blogs">
                {Object.values(blogs)
                  .filter(b => b.id !== blog.id)
                  .slice(0, 3)
                  .map(relatedBlog => (
                    <Link key={relatedBlog.id} to={`/blog/${relatedBlog.id}`} className="related-blog-item">
                      <div className="related-blog-image">
                        <span>{relatedBlog.image}</span>
                      </div>
                      <div className="related-blog-content">
                        <h4>{relatedBlog.title}</h4>
                        <span className="related-blog-date">{relatedBlog.date}</span>
                      </div>
                    </Link>
                  ))}
              </div>
            </div>

            <div className="sidebar-card">
              <h3>Categories</h3>
              <div className="categories-list">
                <Link to="/#blogs" className="category-item">
                  <FiTag />
                  Digital Education Platform
                </Link>
                <Link to="/#blogs" className="category-item">
                  <FiTag />
                  Teacher Self Care Strategies
                </Link>
                <Link to="/#blogs" className="category-item">
                  <FiTag />
                  Study Tips
                </Link>
              </div>
            </div>
          </aside>
        </div>
      </div>
    </div>
  )
}

export default BlogPage

