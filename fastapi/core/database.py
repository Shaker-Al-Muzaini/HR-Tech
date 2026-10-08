"""
core/database.py — اتصال قاعدة البيانات (async PostgreSQL)
"""
from sqlalchemy.ext.asyncio import create_async_engine, AsyncSession, async_sessionmaker
from sqlalchemy.orm import DeclarativeBase
from sqlalchemy import text
from core.config import settings


engine = create_async_engine(
    settings.DB_URL,
    echo=(settings.ENVIRONMENT == "development"),
    pool_size=10,
    max_overflow=20,
)

AsyncSessionLocal = async_sessionmaker(
    engine,
    class_=AsyncSession,
    expire_on_commit=False,
)


class Base(DeclarativeBase):
    pass


async def init_db():
    """
    فحص الاتصال بقاعدة البيانات فقط.
    الجداول تُدار بواسطة Laravel migrations — لا ننشئ جداول من هنا.
    """
    async with engine.begin() as conn:
        await conn.execute(text("SELECT 1"))
    print("✅ Database connection verified")


async def get_db():
    """Dependency لاستخدامه في الـ endpoints"""
    async with AsyncSessionLocal() as session:
        try:
            yield session
            await session.commit()
        except Exception:
            await session.rollback()
            raise
        finally:
            await session.close()